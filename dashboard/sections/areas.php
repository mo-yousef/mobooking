<?php
// dashboard/sections/areas.php - Enhanced Service Areas Management with Real API Integration
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

// Get user's selected country
$selected_country = get_user_meta($user_id, 'mobooking_service_country', true);

// Countries supported with comprehensive city lists and API integration
$supported_countries = array(
    'US' => array('name' => 'United States', 'has_api' => true),
    'CA' => array('name' => 'Canada', 'has_api' => true),
    'GB' => array('name' => 'United Kingdom', 'has_api' => true),
    'DE' => array('name' => 'Germany', 'has_api' => true),
    'FR' => array('name' => 'France', 'has_api' => true),
    'ES' => array('name' => 'Spain', 'has_api' => true),
    'IT' => array('name' => 'Italy', 'has_api' => true),
    'AU' => array('name' => 'Australia', 'has_api' => true),
    'SE' => array('name' => 'Sweden', 'has_api' => true),
    'CY' => array('name' => 'Cyprus', 'has_api' => true),
    'NL' => array('name' => 'Netherlands', 'has_api' => true),
    'BE' => array('name' => 'Belgium', 'has_api' => true),
    'CH' => array('name' => 'Switzerland', 'has_api' => true),
    'AT' => array('name' => 'Austria', 'has_api' => true),
    'NO' => array('name' => 'Norway', 'has_api' => true),
    'DK' => array('name' => 'Denmark', 'has_api' => true),
    'FI' => array('name' => 'Finland', 'has_api' => true)
);
?>

<div class="areas-section enhanced-areas">
    <div class="areas-header">
        <div class="areas-header-content">
            <div class="areas-title-group">
                <h1 class="areas-main-title">
                    <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <?php _e('Service Areas', 'mobooking'); ?>
                </h1>
                <p class="areas-subtitle"><?php _e('Manage cities and neighborhoods where you provide services', 'mobooking'); ?></p>
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
                    <?php if ($selected_country) : ?>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo esc_html($supported_countries[$selected_country]['name'] ?? $selected_country); ?></span>
                        <span class="stat-label"><?php _e('Country', 'mobooking'); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="areas-header-actions">
            <?php if ($selected_country && !empty($areas)) : ?>
                <button type="button" id="add-city-btn" class="btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <?php _e('Add City', 'mobooking'); ?>
                </button>
                <button type="button" id="change-country-btn" class="btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                    </svg>
                    <?php _e('Change Country', 'mobooking'); ?>
                </button>
            <?php elseif ($selected_country) : ?>
                <button type="button" id="add-first-city-btn" class="btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <?php _e('Add Your First City', 'mobooking'); ?>
                </button>
                <button type="button" id="change-country-btn" class="btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                    </svg>
                    <?php _e('Change Country', 'mobooking'); ?>
                </button>
            <?php else : ?>
                <button type="button" id="select-country-btn" class="btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                    <?php _e('Select Country', 'mobooking'); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!$selected_country) : ?>
        <!-- Step 1: Country Selection -->
        <div class="country-selection-container">
            <div class="country-selection-card">
                <div class="selection-header">
                    <div class="selection-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        </svg>
                    </div>
                    <h2><?php _e('Select Your Service Country', 'mobooking'); ?></h2>
                    <p><?php _e('Choose the country where you provide services. We\'ll fetch real area data using external APIs.', 'mobooking'); ?></p>
                </div>
                
                <div class="country-selection-form">
                    <div class="form-group">
                        <label for="country-select"><?php _e('Country', 'mobooking'); ?></label>
                        <select id="country-select" class="country-dropdown">
                            <option value=""><?php _e('Select a country...', 'mobooking'); ?></option>
                            <?php foreach ($supported_countries as $code => $info) : ?>
                                <option value="<?php echo esc_attr($code); ?>" data-has-api="<?php echo $info['has_api'] ? '1' : '0'; ?>">
                                    <?php echo esc_html($info['name']); ?>
                                    <?php if ($info['has_api']) : ?>
                                        <span class="api-indicator">🌐</span>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="field-help"><?php _e('Countries with 🌐 have real-time area data available via external APIs.', 'mobooking'); ?></p>
                    </div>
                    
                    <button type="button" id="confirm-country-btn" class="btn-primary" disabled>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6 9 17l-5-5"/>
                        </svg>
                        <?php _e('Confirm Country Selection', 'mobooking'); ?>
                    </button>
                </div>
            </div>
        </div>
    <?php elseif (empty($areas)) : ?>
        <!-- Step 2: Add First City -->
        <div class="first-city-container">
            <div class="first-city-card">
                <div class="selection-header">
                    <div class="selection-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                    <h2><?php printf(__('Add Your First City in %s', 'mobooking'), $supported_countries[$selected_country]['name']); ?></h2>
                    <p><?php _e('Enter a city name and we\'ll fetch all available areas and neighborhoods with ZIP codes from external data sources.', 'mobooking'); ?></p>
                </div>
                
                <div class="city-input-form">
                    <div class="form-group">
                        <label for="city-name-input"><?php _e('City Name', 'mobooking'); ?></label>
                        <input type="text" id="city-name-input" class="city-input" 
                               placeholder="<?php _e('Enter city name (e.g., New York, London, Paris)', 'mobooking'); ?>" 
                               autocomplete="off">
                        <div class="city-suggestions" id="city-suggestions" style="display: none;"></div>
                        <p class="field-help"><?php _e('Start typing to see suggestions, or enter any city name.', 'mobooking'); ?></p>
                    </div>
                    
                    <button type="button" id="fetch-city-areas-btn" class="btn-primary btn-large" disabled>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12a9 9 0 11-6.219-8.56"/>
                        </svg>
                        <span class="btn-text"><?php _e('Get Areas for This City', 'mobooking'); ?></span>
                        <span class="btn-loading" style="display: none;"><?php _e('Fetching...', 'mobooking'); ?></span>
                    </button>
                </div>
            </div>
        </div>
    <?php else : ?>
        <!-- Step 3: Manage Existing Areas -->
        <div class="areas-management">
            <!-- Quick Stats & Actions -->
            <div class="areas-toolbar">
                <div class="toolbar-section">
                    <div class="country-indicator">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M2 12h20"/>
                        </svg>
                        <span><?php echo esc_html($supported_countries[$selected_country]['name']); ?></span>
                    </div>
                </div>
                
                <div class="toolbar-section">
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
                        <input type="text" id="areas-search-input" placeholder="<?php _e('Search areas or ZIP codes...', 'mobooking'); ?>" class="search-input">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Areas Grid -->
            <div class="areas-container">
                <div class="areas-grid-header">
                    <div class="grid-header-cell select-all">
                        <input type="checkbox" id="select-all-areas" class="select-checkbox">
                    </div>
                    <div class="grid-header-cell area-name"><?php _e('Area Name', 'mobooking'); ?></div>
                    <div class="grid-header-cell zip-code"><?php _e('ZIP Code', 'mobooking'); ?></div>
                    <div class="grid-header-cell city"><?php _e('City', 'mobooking'); ?></div>
                    <div class="grid-header-cell status"><?php _e('Status', 'mobooking'); ?></div>
                    <div class="grid-header-cell last-updated"><?php _e('Last Updated', 'mobooking'); ?></div>
                    <div class="grid-header-cell actions"><?php _e('Actions', 'mobooking'); ?></div>
                </div>
                
                <div class="areas-grid-body" id="areas-list">
                    <?php foreach ($areas as $area) : 
                        // Parse ZIP codes from the stored data
                        $zip_codes = !empty($area->zip_codes) ? json_decode($area->zip_codes, true) : array();
                        if (!is_array($zip_codes)) {
                            $zip_codes = !empty($area->zip_code) ? array($area->zip_code) : array();
                        }
                        $main_zip = !empty($zip_codes) ? $zip_codes[0] : ($area->zip_code ?: 'N/A');
                        $area_name = $area->city_name ?: $area->label ?: 'Unnamed Area';
                    ?>
                        <div class="area-row enhanced" data-area-id="<?php echo esc_attr($area->id); ?>" data-area-name="<?php echo esc_attr($area_name); ?>">
                            <div class="grid-cell select">
                                <input type="checkbox" class="area-checkbox" value="<?php echo esc_attr($area->id); ?>">
                            </div>
                            <div class="grid-cell area-name">
                                <div class="area-info">
                                    <span class="area-name-text"><?php echo esc_html($area_name); ?></span>
                                    <?php if (!empty($area->description)) : ?>
                                        <span class="area-description"><?php echo esc_html($area->description); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="grid-cell zip-code">
                                <div class="zip-info">
                                    <span class="zip-code-main"><?php echo esc_html($main_zip); ?></span>
                                    <?php if (count($zip_codes) > 1) : ?>
                                        <span class="zip-additional">+<?php echo (count($zip_codes) - 1); ?> more</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="grid-cell city">
                                <span class="city-name"><?php echo esc_html($area->state ? $area->state : ($area->country ?: 'N/A')); ?></span>
                            </div>
                            <div class="grid-cell status">
                                <span class="status-badge <?php echo $area->active ? 'active' : 'inactive'; ?>">
                                    <?php if ($area->active) : ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                                        </svg>
                                        <?php _e('Active', 'mobooking'); ?>
                                    <?php else : ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <path d="M8 12h8"/>
                                        </svg>
                                        <?php _e('Inactive', 'mobooking'); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="grid-cell last-updated">
                                <time datetime="<?php echo esc_attr($area->updated_at); ?>">
                                    <?php echo esc_html(human_time_diff(strtotime($area->updated_at), current_time('timestamp')) . ' ago'); ?>
                                </time>
                            </div>
                            <div class="grid-cell actions">
                                <div class="action-buttons">
                                    <button type="button" class="btn-icon toggle-area-btn" data-area-id="<?php echo esc_attr($area->id); ?>" data-active="<?php echo $area->active ? '1' : '0'; ?>" title="<?php echo $area->active ? __('Deactivate', 'mobooking') : __('Activate', 'mobooking'); ?>">
                                        <?php if ($area->active) : ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <path d="M8 12h8"/>
                                            </svg>
                                        <?php else : ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                                            </svg>
                                        <?php endif; ?>
                                    </button>
                                    <button type="button" class="btn-icon btn-danger delete-area-btn" data-area-id="<?php echo esc_attr($area->id); ?>" title="<?php _e('Delete Area', 'mobooking'); ?>">
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
        </div>
    <?php endif; ?>
</div>

<!-- Add City Modal -->
<div id="add-city-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-content modal-lg">
        <button class="modal-close" aria-label="<?php _e('Close', 'mobooking'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </button>
        
        <h3 id="add-city-modal-title"><?php _e('Add City Areas', 'mobooking'); ?></h3>
        
        <div class="add-city-content">
            <div class="city-input-section">
                <div class="form-group">
                    <label for="modal-city-input"><?php _e('City Name', 'mobooking'); ?></label>
                    <input type="text" id="modal-city-input" class="city-input" 
                           placeholder="<?php _e('Enter city name', 'mobooking'); ?>" 
                           autocomplete="off">
                    <div class="city-suggestions" id="modal-city-suggestions" style="display: none;"></div>
                </div>
                
                <button type="button" id="modal-fetch-areas-btn" class="btn-primary" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12a9 9 0 11-6.219-8.56"/>
                    </svg>
                    <span class="btn-text"><?php _e('Get Areas', 'mobooking'); ?></span>
                    <span class="btn-loading" style="display: none;"><?php _e('Fetching...', 'mobooking'); ?></span>
                </button>
            </div>
            
            <div class="areas-results-section" id="areas-results" style="display: none;">
                <div class="results-header">
                    <h4><?php _e('Available Areas', 'mobooking'); ?></h4>
                    <div class="results-actions">
                        <button type="button" id="select-all-areas-btn" class="btn-secondary btn-sm">
                            <?php _e('Select All', 'mobooking'); ?>
                        </button>
                        <button type="button" id="clear-all-areas-btn" class="btn-secondary btn-sm">
                            <?php _e('Clear All', 'mobooking'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="areas-grid" id="fetched-areas-grid">
                    <!-- Areas will be populated here -->
                </div>
                
                <div class="areas-summary">
                    <div class="selected-count">
                        <span id="selected-areas-count">0</span> <?php _e('areas selected', 'mobooking'); ?>
                    </div>
                    <button type="button" id="save-selected-areas-btn" class="btn-primary btn-large" disabled>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17,21 17,13 7,13 7,21"/>
                            <polyline points="7,3 7,8 15,8"/>
                        </svg>
                        <span class="btn-text"><?php _e('Save Selected Areas', 'mobooking'); ?></span>
                        <span class="btn-loading" style="display: none;"><?php _e('Saving...', 'mobooking'); ?></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Country Selection Confirmation Modal -->
<div id="country-confirmation-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-content">
        <h3><?php _e('Confirm Country Selection', 'mobooking'); ?></h3>
        <div class="confirmation-content">
            <div class="warning-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <p id="country-confirmation-message"><?php _e('You are about to select [COUNTRY] as your service country. This cannot be changed later without losing all your current service areas. Are you sure?', 'mobooking'); ?></p>
        </div>
        
        <div class="form-actions">
            <button type="button" class="btn-secondary cancel-country-btn">
                <?php _e('Cancel', 'mobooking'); ?>
            </button>
            <button type="button" class="btn-primary confirm-country-selection-btn">
                <span class="btn-text"><?php _e('Yes, Select This Country', 'mobooking'); ?></span>
                <span class="btn-loading" style="display: none;"><?php _e('Saving...', 'mobooking'); ?></span>
            </button>
        </div>
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
                <span class="btn-loading" style="display: none;"><?php _e('Processing...', 'mobooking'); ?></span>
            </button>
        </div>
    </div>
</div>

<style>
/* Enhanced Areas Management CSS */
.enhanced-areas {
    --primary: #3b82f6;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-400: #9ca3af;
    --gray-500: #6b7280;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-800: #1f2937;
    --gray-900: #111827;
}

/* Layout */
.areas-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
.areas-header-content { flex: 1; min-width: 300px; }
.areas-title-group h1 { font-size: 1.875rem; font-weight: 700; color: var(--gray-900); margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 0.75rem; }
.areas-title-group .title-icon { width: 32px; height: 32px; color: var(--primary); }
.areas-subtitle { color: var(--gray-600); margin: 0; font-size: 1rem; }
.areas-stats { display: flex; gap: 1.5rem; margin-top: 1rem; }
.stat-item { text-align: center; }
.stat-number { display: block; font-size: 1.5rem; font-weight: 700; color: var(--primary); }
.stat-label { font-size: 0.875rem; color: var(--gray-500); }
.areas-header-actions { display: flex; gap: 0.75rem; align-items: flex-start; }

/* Country Selection */
.country-selection-container { display: flex; justify-content: center; align-items: center; min-height: 60vh; padding: 2rem; }
.country-selection-card { background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 3rem; max-width: 500px; width: 100%; text-align: center; }
.selection-header { margin-bottom: 2rem; }
.selection-icon { width: 64px; height: 64px; background: linear-gradient(135deg, var(--primary), #1d4ed8); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: white; }
.selection-icon svg { width: 32px; height: 32px; }
.country-selection-form .form-group { margin-bottom: 1.5rem; text-align: left; }
.country-dropdown { width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 8px; font-size: 1rem; transition: all 0.2s; }
.country-dropdown:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }

/* First City Addition */
.first-city-container { display: flex; justify-content: center; align-items: center; min-height: 60vh; padding: 2rem; }
.first-city-card { background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 3rem; max-width: 600px; width: 100%; text-align: center; }
.city-input-form .form-group { margin-bottom: 1.5rem; text-align: left; position: relative; }
.city-input { width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 8px; font-size: 1rem; transition: all 0.2s; }
.city-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.city-suggestions { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid var(--gray-200); border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 1000; max-height: 200px; overflow-y: auto; }
.city-suggestion { padding: 0.75rem 1rem; cursor: pointer; transition: background 0.2s; border-bottom: 1px solid var(--gray-100); }
.city-suggestion:hover { background: var(--gray-50); }
.city-suggestion:last-child { border-bottom: none; }

/* Toolbar */
.areas-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding: 1rem; background: var(--gray-50); border-radius: 8px; flex-wrap: wrap; gap: 1rem; }
.toolbar-section { display: flex; align-items: center; gap: 1rem; }
.country-indicator { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: var(--gray-100); border-radius: 6px; font-weight: 500; color: var(--gray-700); }
.country-indicator svg { width: 18px; height: 18px; }
.bulk-actions { display: flex; align-items: center; gap: 0.5rem; }
.bulk-actions select { padding: 0.5rem; border: 1px solid var(--gray-300); border-radius: 6px; }
.areas-search { position: relative; }
.search-input { padding: 0.5rem 2.5rem 0.5rem 1rem; border: 1px solid var(--gray-300); border-radius: 6px; width: 250px; }
.search-icon { position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--gray-400); }

/* Areas Grid */
.areas-container { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
.areas-grid-header { display: grid; grid-template-columns: 40px 2fr 1fr 1fr 100px 120px 120px; gap: 1rem; padding: 1rem; background: var(--gray-50); border-bottom: 1px solid var(--gray-200); font-weight: 600; color: var(--gray-700); }
.areas-grid-body { max-height: 600px; overflow-y: auto; }
.area-row { display: grid; grid-template-columns: 40px 2fr 1fr 1fr 100px 120px 120px; gap: 1rem; padding: 1rem; border-bottom: 1px solid var(--gray-100); align-items: center; transition: all 0.2s; border-left: 4px solid transparent; }
.area-row:hover { background: var(--gray-50); border-left-color: var(--primary); }
.area-row:last-child { border-bottom: none; }

/* Grid Cells */
.grid-cell { display: flex; align-items: center; }
.select-checkbox, .area-checkbox { margin: 0; }
.area-info { display: flex; flex-direction: column; gap: 0.25rem; }
.area-name-text { font-weight: 600; color: var(--gray-900); }
.area-description { font-size: 0.875rem; color: var(--gray-500); }
.zip-info { display: flex; flex-direction: column; gap: 0.25rem; }
.zip-code-main { font-weight: 500; color: var(--gray-700); font-family: 'Courier New', monospace; }
.zip-additional { font-size: 0.875rem; color: var(--primary); font-weight: 500; }
.city-name { font-weight: 500; color: var(--gray-700); }

/* Status Badge */
.status-badge { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.375rem 0.75rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; }
.status-badge.active { background: #dcfce7; color: #166534; }
.status-badge.inactive { background: #fef3c7; color: #92400e; }
.status-badge svg { width: 16px; height: 16px; }

/* Action Buttons */
.action-buttons { display: flex; gap: 0.25rem; }
.btn-icon { width: 32px; height: 32px; border: none; background: none; color: var(--gray-600); border-radius: 4px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; }
.btn-icon:hover { background: var(--gray-100); color: var(--gray-900); }
.btn-icon.btn-danger:hover { background: #fee2e2; color: var(--danger); }
.btn-icon svg { width: 16px; height: 16px; }

/* Modal Styles */
.mobooking-modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; padding: 1rem; }
.modal-content { background: white; border-radius: 12px; max-width: 600px; width: 100%; margin: 2rem auto; position: relative; max-height: 90vh; overflow-y: auto; padding: 2rem; }
.modal-lg { max-width: 900px; }
.modal-close { position: absolute; top: 1rem; right: 1rem; width: 32px; height: 32px; border: none; background: var(--gray-100); border-radius: 50%; color: var(--gray-600); cursor: pointer; display: flex; align-items: center; justify-content: center; }
.modal-close:hover { background: var(--gray-200); }

/* Add City Modal */
.add-city-content { display: flex; flex-direction: column; gap: 2rem; }
.city-input-section { padding-bottom: 2rem; border-bottom: 1px solid var(--gray-200); }
.areas-results-section { padding-top: 2rem; }
.results-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.results-actions { display: flex; gap: 0.5rem; }
.areas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; margin-bottom: 2rem; max-height: 400px; overflow-y: auto; padding: 0.5rem; }
.area-card { padding: 1rem; border: 2px solid var(--gray-200); border-radius: 8px; background: white; transition: all 0.2s; cursor: pointer; }
.area-card:hover { border-color: var(--primary); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1); }
.area-card.selected { border-color: var(--primary); background: rgba(59, 130, 246, 0.05); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15); }
.area-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; }
.area-card-title { font-weight: 600; color: var(--gray-900); margin: 0; }
.area-card-zip { font-family: 'Courier New', monospace; font-weight: 500; color: var(--primary); background: rgba(59, 130, 246, 0.1); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.875rem; }
.area-card-meta { display: flex; flex-direction: column; gap: 0.25rem; }
.area-card-source { font-size: 0.875rem; color: var(--gray-500); }
.area-card-coordinates { font-size: 0.75rem; color: var(--gray-400); font-family: 'Courier New', monospace; }
.areas-summary { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; background: var(--gray-50); border-radius: 8px; margin-top: 1rem; }
.selected-count { font-size: 1.125rem; font-weight: 600; color: var(--gray-700); }

/* Forms */
.form-group { margin-bottom: 1.5rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--gray-700); }
.form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 6px; font-size: 1rem; transition: all 0.2s; }
.form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.field-help { font-size: 0.875rem; color: var(--gray-500); margin-top: 0.25rem; }

/* Confirmation Modal */
.confirmation-content { text-align: center; padding: 1rem 0; }
.warning-icon { width: 64px; height: 64px; background: var(--warning); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; }
.warning-icon svg { width: 32px; height: 32px; }

/* Form Actions */
.form-actions { display: flex; align-items: center; gap: 1rem; padding: 1.5rem; border-top: 1px solid var(--gray-200); margin: 1rem -2rem -2rem; }
.spacer { flex: 1; }

/* Buttons */
.btn-primary, .btn-secondary, .btn-danger { padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; border: none; }
.btn-primary { background: var(--primary); color: white; }
.btn-primary:hover:not(:disabled) { background: #2563eb; }
.btn-secondary { background: var(--gray-100); color: var(--gray-700); }
.btn-secondary:hover:not(:disabled) { background: var(--gray-200); }
.btn-danger:hover:not(:disabled) { background: #dc2626; }
.btn-sm { padding: 0.5rem 1rem; font-size: 0.875rem; }
.btn-large { padding: 1rem 2rem; font-size: 1.125rem; }

/* Button Loading States */
.loading .btn-text { display: none; }
.loading .btn-loading { display: inline-flex; }
.btn-loading { display: none; }

/* API Indicator */
.api-indicator { margin-left: 0.5rem; }

/* Responsive */
@media (max-width: 768px) {
    .areas-header { flex-direction: column; align-items: stretch; }
    .areas-stats { justify-content: space-around; }
    .areas-toolbar { flex-direction: column; align-items: stretch; }
    .toolbar-section { flex-wrap: wrap; }
    .areas-search .search-input { width: 100%; }
    .areas-grid-header { display: none; }
    .area-row { display: flex; flex-direction: column; gap: 1rem; padding: 1rem; border: 1px solid var(--gray-200); border-radius: 8px; margin-bottom: 0.75rem; }
    .grid-cell { justify-content: space-between; }
    .modal-lg { max-width: 95vw; margin: 1rem; }
    .country-selection-card, .first-city-card { padding: 2rem 1.5rem; }
    .areas-grid { grid-template-columns: 1fr; }
    .areas-summary { flex-direction: column; gap: 1rem; text-align: center; }
}

@media (max-width: 480px) {
    .country-selection-card, .first-city-card { padding: 1.5rem 1rem; }
    .form-actions { flex-direction: column-reverse; align-items: stretch; }
    .btn-primary, .btn-secondary, .btn-danger { width: 100%; justify-content: center; }
    .modal-content { padding: 1rem; margin: 0.5rem; }
}

/* Animations */
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.area-row { animation: fadeIn 0.3s ease-out; }
.area-card { animation: fadeIn 0.2s ease-out; }

/* Focus Styles */
.btn-primary:focus, .btn-secondary:focus, .btn-danger:focus { outline: 2px solid var(--primary); outline-offset: 2px; }
.form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
</style>

<script>
// Enhanced Areas Management JavaScript with Real API Integration
jQuery(document).ready(function($) {
    const EnhancedAreasManager = {
        config: {
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('mobooking-area-nonce'); ?>',
            userId: <?php echo $user_id; ?>,
            selectedCountry: '<?php echo esc_js($selected_country); ?>',
            supportedCountries: <?php echo json_encode($supported_countries); ?>
        },
        
        state: {
            isProcessing: false,
            currentAreaId: null,
            selectedAreas: [],
            fetchedAreas: [],
            currentCity: '',
            confirmAction: null
        },
        
        init: function() {
            console.log('🚀 Enhanced Areas Manager with API integration initializing...');
            this.attachEventListeners();
            this.initializeComponents();
            console.log('✅ Enhanced Areas Manager initialized');
        },
        
        attachEventListeners: function() {
            const self = this;
            
            // Country selection
            $('#country-select').on('change', function() {
                const selectedCountry = $(this).val();
                $('#confirm-country-btn').prop('disabled', !selectedCountry);
                
                if (selectedCountry) {
                    const countryName = self.config.supportedCountries[selectedCountry].name;
                    $('#confirm-country-btn').find('.btn-text').text('Confirm ' + countryName);
                }
            });
            
            $('#select-country-btn, #confirm-country-btn').on('click', function() {
                self.showCountryConfirmation();
            });
            
            $('#change-country-btn').on('click', function() {
                self.changeCountry();
            });
            
            // City input and fetching
            $('#city-name-input, #modal-city-input').on('input', function() {
                const cityName = $(this).val().trim();
                const targetBtn = $(this).attr('id') === 'city-name-input' ? '#fetch-city-areas-btn' : '#modal-fetch-areas-btn';
                $(targetBtn).prop('disabled', cityName.length < 2);
                
                // Show city suggestions (if available)
                if (cityName.length >= 2) {
                    self.showCitySuggestions($(this), cityName);
                } else {
                    self.hideCitySuggestions($(this));
                }
            });
            
            // City suggestions
            $(document).on('click', '.city-suggestion', function() {
                const cityName = $(this).text();
                const input = $(this).closest('.form-group').find('.city-input');
                input.val(cityName);
                self.hideCitySuggestions(input);
                
                const targetBtn = input.attr('id') === 'city-name-input' ? '#fetch-city-areas-btn' : '#modal-fetch-areas-btn';
                $(targetBtn).prop('disabled', false);
            });
            
            // Fetch city areas
            $('#fetch-city-areas-btn, #add-first-city-btn, #add-city-btn, #modal-fetch-areas-btn').on('click', function() {
                let cityName = '';
                
                if ($(this).attr('id') === 'modal-fetch-areas-btn') {
                    cityName = $('#modal-city-input').val().trim();
                    if (!cityName) {
                        self.showNotification('Please enter a city name', 'warning');
                        return;
                    }
                    self.fetchCityAreas(cityName, true); // true = in modal
                } else if ($(this).attr('id') === 'fetch-city-areas-btn') {
                    cityName = $('#city-name-input').val().trim();
                    if (!cityName) {
                        self.showNotification('Please enter a city name', 'warning');
                        return;
                    }
                    self.fetchCityAreas(cityName, false); // false = not in modal
                } else {
                    // Add city button clicked - show modal
                    self.showModal('#add-city-modal');
                }
            });
            
            // Area selection in modal
            $(document).on('click', '.area-card', function() {
                const checkbox = $(this).find('.area-checkbox');
                const isSelected = checkbox.is(':checked');
                checkbox.prop('checked', !isSelected);
                $(this).toggleClass('selected', !isSelected);
                self.updateSelectedAreasCount();
            });
            
            $(document).on('change', '.area-checkbox', function() {
                const isSelected = $(this).is(':checked');
                $(this).closest('.area-card').toggleClass('selected', isSelected);
                self.updateSelectedAreasCount();
            });
            
            // Area selection actions
            $('#select-all-areas-btn').on('click', function() {
                $('.area-checkbox:visible').prop('checked', true);
                $('.area-card:visible').addClass('selected');
                self.updateSelectedAreasCount();
            });
            
            $('#clear-all-areas-btn').on('click', function() {
                $('.area-checkbox').prop('checked', false);
                $('.area-card').removeClass('selected');
                self.updateSelectedAreasCount();
            });
            
            // Save selected areas
            $('#save-selected-areas-btn').on('click', function() {
                self.saveSelectedAreas();
            });
            
            // Area management
            $(document).on('click', '.toggle-area-btn', function() {
                const areaId = $(this).data('area-id');
                const isActive = $(this).data('active') === 1;
                self.toggleAreaStatus(areaId, !isActive);
            });
            
            $(document).on('click', '.delete-area-btn', function() {
                const areaId = $(this).data('area-id');
                self.deleteArea(areaId);
            });
            
            // Bulk actions
            $('#apply-bulk-action').on('click', function() {
                self.applyBulkAction();
            });
            
            // Select all checkbox
            $('#select-all-areas').on('change', function() {
                $('.area-checkbox').prop('checked', $(this).is(':checked'));
            });
            
            // Search functionality
            $('#areas-search-input').on('input', function() {
                self.filterAreas($(this).val());
            });
            
            // Modal controls
            $('.modal-close, .cancel-action-btn, .cancel-country-btn').on('click', function() {
                self.hideModals();
            });
            
            // Confirmation actions
            $('.confirm-country-selection-btn').on('click', function() {
                self.confirmCountrySelection();
            });
            
            $('.confirm-action-btn').on('click', function() {
                self.executeConfirmedAction();
            });
            
            // Hide suggestions when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.form-group').length) {
                    $('.city-suggestions').hide();
                }
            });
        },
        
        initializeComponents: function() {
            this.updateSelectedAreasCount();
        },
        
        showCitySuggestions: function($input, searchTerm) {
            const $suggestions = $input.siblings('.city-suggestions');
            
            // Get city suggestions for the selected country
            if (this.config.selectedCountry && this.config.supportedCountries[this.config.selectedCountry]) {
                // In a real implementation, you would fetch cities from an API
                // For now, we'll show a simple message
                $suggestions.html(`
                    <div class="city-suggestion">
                        <strong>${searchTerm}</strong> - Search for this city
                    </div>
                `).show();
            } else {
                $suggestions.hide();
            }
        },
        
        hideCitySuggestions: function($input) {
            $input.siblings('.city-suggestions').hide();
        },
        
        fetchCityAreas: function(cityName, inModal = false) {
            if (this.state.isProcessing) return;
            
            this.state.isProcessing = true;
            this.state.currentCity = cityName;
            
            const $btn = inModal ? $('#modal-fetch-areas-btn') : $('#fetch-city-areas-btn');
            this.setLoading($btn, true);
            
            const data = {
                action: 'mobooking_fetch_city_areas',
                city_name: cityName,
                country_code: this.config.selectedCountry,
                state: '', // You could add state selection
                nonce: this.config.nonce
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                timeout: 30000, // 30 second timeout for API calls
                success: (response) => {
                    if (response.success) {
                        this.state.fetchedAreas = response.data.areas;
                        this.displayFetchedAreas(response.data.areas, inModal);
                        this.showNotification(`Found ${response.data.total_areas} areas in ${cityName}`, 'success');
                    } else {
                        this.showNotification(response.data?.message || 'Failed to fetch areas', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    if (status === 'timeout') {
                        this.showNotification('Request timed out. Please try again.', 'error');
                    } else {
                        this.showNotification('Error fetching city areas', 'error');
                    }
                },
                complete: () => {
                    this.state.isProcessing = false;
                    this.setLoading($btn, false);
                }
            });
        },
        
        displayFetchedAreas: function(areas, inModal = false) {
            const containerId = inModal ? '#fetched-areas-grid' : '#areas-results-grid';
            const $container = $(containerId);
            const $resultsSection = inModal ? $('#areas-results') : $('#areas-results-section');
            
            $container.empty();
            
            if (!areas || areas.length === 0) {
                $container.html(`
                    <div class="no-areas-message" style="text-align: center; padding: 2rem; grid-column: 1 / -1;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 48px; height: 48px; margin-bottom: 1rem; color: #9ca3af;">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <h3 style="color: #6b7280; margin: 0 0 0.5rem 0;">No areas found</h3>
                        <p style="color: #9ca3af;">Try a different city name or check the spelling.</p>
                    </div>
                `);
            } else {
                areas.forEach((area, index) => {
                    const areaCard = this.createAreaCard(area, index);
                    $container.append(areaCard);
                });
            }
            
            $resultsSection.show();
            this.updateSelectedAreasCount();
        },
        
        createAreaCard: function(area, index) {
            const coordinates = area.latitude && area.longitude ? 
                `${parseFloat(area.latitude).toFixed(4)}, ${parseFloat(area.longitude).toFixed(4)}` : 
                'Coordinates not available';
            
            return $(`
                <div class="area-card" data-area-index="${index}">
                    <input type="checkbox" class="area-checkbox" value="${index}" style="position: absolute; opacity: 0; pointer-events: none;">
                    <div class="area-card-header">
                        <h4 class="area-card-title">${this.escapeHtml(area.area_name)}</h4>
                        <div class="area-card-zip">${this.escapeHtml(area.zip_code)}</div>
                    </div>
                    <div class="area-card-meta">
                        <div class="area-card-source">Source: ${this.escapeHtml(area.source)}</div>
                        ${coordinates !== 'Coordinates not available' ? `<div class="area-card-coordinates">${coordinates}</div>` : ''}
                    </div>
                </div>
            `);
        },
        
        updateSelectedAreasCount: function() {
            const selectedCount = $('.area-checkbox:checked').length;
            $('#selected-areas-count').text(selectedCount);
            $('#save-selected-areas-btn').prop('disabled', selectedCount === 0);
            
            if (selectedCount > 0) {
                $('#save-selected-areas-btn').find('.btn-text').text(`Save ${selectedCount} Selected Areas`);
            } else {
                $('#save-selected-areas-btn').find('.btn-text').text('Save Selected Areas');
            }
        },
        
        saveSelectedAreas: function() {
            const selectedIndices = $('.area-checkbox:checked').map((i, el) => parseInt($(el).val())).get();
            
            if (selectedIndices.length === 0) {
                this.showNotification('Please select at least one area', 'warning');
                return;
            }
            
            // Get selected areas from fetched data
            const selectedAreas = selectedIndices.map(index => this.state.fetchedAreas[index]);
            
            if (this.state.isProcessing) return;
            
            this.state.isProcessing = true;
            const $btn = $('#save-selected-areas-btn');
            this.setLoading($btn, true);
            
            const data = {
                action: 'mobooking_save_selected_areas',
                areas_data: JSON.stringify(selectedAreas),
                city_name: this.state.currentCity,
                country_code: this.config.selectedCountry,
                state: '', // Add state if needed
                nonce: this.config.nonce
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                timeout: 60000, // 60 second timeout for database operations
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        this.hideModals();
                        
                        // Refresh the page to show new areas
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        this.showNotification(response.data?.message || 'Failed to save areas', 'error');
                        
                        // Show additional error details if available
                        if (response.data?.errors && response.data.errors.length > 0) {
                            console.error('Save errors:', response.data.errors);
                        }
                    }
                },
                error: (xhr, status, error) => {
                    if (status === 'timeout') {
                        this.showNotification('Save operation timed out. Please try again.', 'error');
                    } else {
                        this.showNotification('Error saving areas', 'error');
                    }
                    console.error('AJAX Error:', xhr.responseText);
                },
                complete: () => {
                    this.state.isProcessing = false;
                    this.setLoading($btn, false);
                }
            });
        },
        
        showCountryConfirmation: function() {
            const selectedCountry = $('#country-select').val();
            if (!selectedCountry) {
                this.showNotification('Please select a country first', 'warning');
                return;
            }
            
            const countryName = this.config.supportedCountries[selectedCountry].name;
            const message = '<?php _e('You are about to select [COUNTRY] as your service country. This cannot be changed later without losing all your current service areas. Are you sure?', 'mobooking'); ?>';
            
            $('#country-confirmation-message').text(message.replace('[COUNTRY]', countryName));
            this.showModal('#country-confirmation-modal');
        },
        
        confirmCountrySelection: function() {
            const selectedCountry = $('#country-select').val();
            
            if (this.state.isProcessing) return;
            
            this.state.isProcessing = true;
            const $btn = $('.confirm-country-selection-btn');
            this.setLoading($btn, true);
            
            const data = {
                action: 'mobooking_set_service_country',
                country: selectedCountry,
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
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        this.showNotification(response.data?.message || 'Failed to set country', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Error setting country', 'error');
                },
                complete: () => {
                    this.state.isProcessing = false;
                    this.setLoading($btn, false);
                }
            });
        },
        
        changeCountry: function() {
            const message = '<?php _e('Changing your service country will delete all existing service areas. This action cannot be undone. Are you sure?', 'mobooking'); ?>';
            $('#confirmation-message').text(message);
            this.state.confirmAction = 'change_country';
            this.showModal('#confirmation-modal');
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
                        this.showNotification(response.data.message, 'success');
                        this.refreshAreasDisplay();
                    } else {
                        this.showNotification('Failed to update area status', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Error updating area status', 'error');
                }
            });
        },
        
        deleteArea: function(areaId) {
            this.state.currentAreaId = areaId;
            this.state.confirmAction = 'delete_area';
            
            const $row = $(`.area-row[data-area-id="${areaId}"]`);
            const areaName = $row.find('.area-name-text').text();
            
            const message = `<?php _e('Are you sure you want to delete the service area "[AREA]"? This action cannot be undone.', 'mobooking'); ?>`;
            $('#confirmation-message').text(message.replace('[AREA]', areaName));
            
            this.showModal('#confirmation-modal');
        },
        
        executeConfirmedAction: function() {
            const action = this.state.confirmAction;
            
            if (action === 'delete_area') {
                this.performDeleteArea();
            } else if (action === 'change_country') {
                this.performChangeCountry();
            }
        },
        
        performDeleteArea: function() {
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
                        this.refreshAreasDisplay();
                    } else {
                        this.showNotification('Failed to delete area', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Error deleting area', 'error');
                },
                complete: () => {
                    this.setLoading($btn, false);
                }
            });
        },
        
        performChangeCountry: function() {
            const $btn = $('.confirm-action-btn');
            this.setLoading($btn, true);
            
            const data = {
                action: 'mobooking_reset_service_country',
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
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        this.showNotification('Failed to reset country', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Error resetting country', 'error');
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
            
            // Handle different bulk actions
            if (action === 'delete') {
                this.state.selectedAreas = selectedIds;
                this.state.confirmAction = 'bulk_delete';
                const message = `<?php _e('Are you sure you want to delete [COUNT] service area(s)? This action cannot be undone.', 'mobooking'); ?>`;
                $('#confirmation-message').text(message.replace('[COUNT]', selectedIds.length));
                this.showModal('#confirmation-modal');
                return;
            }
            
            // Execute other bulk actions immediately
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
                        this.refreshAreasDisplay();
                        
                        // Clear selections
                        $('.area-checkbox').prop('checked', false);
                        $('#select-all-areas').prop('checked', false);
                        $('#bulk-action-select').val('');
                    } else {
                        this.showNotification('Bulk action failed', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Error executing bulk action', 'error');
                }
            });
        },
        
        filterAreas: function(searchTerm) {
            const $rows = $('.area-row');
            
            if (!searchTerm) {
                $rows.show();
                return;
            }
            
            searchTerm = searchTerm.toLowerCase();
            
            $rows.each(function() {
                const $row = $(this);
                const areaName = $row.find('.area-name-text').text().toLowerCase();
                const zipCode = $row.find('.zip-code-main').text().toLowerCase();
                const cityName = $row.find('.city-name').text().toLowerCase();
                
                const matches = areaName.includes(searchTerm) || 
                              zipCode.includes(searchTerm) || 
                              cityName.includes(searchTerm);
                
                $row.toggle(matches);
            });
        },
        
        refreshAreasDisplay: function() {
            // Reload the page to refresh the areas display
            // In a more sophisticated implementation, this could be done via AJAX
            location.reload();
        },
        
        showModal: function(selector) {
            $(selector).fadeIn(300);
            $('body').addClass('modal-open');
        },
        
        hideModals: function() {
            $('.mobooking-modal').fadeOut(300);
            $('body').removeClass('modal-open');
            
            // Reset state
            this.state.currentAreaId = null;
            this.state.selectedAreas = [];
            this.state.confirmAction = null;
            this.state.fetchedAreas = [];
            this.state.currentCity = '';
            
            // Clear forms
            $('#city-name-input, #modal-city-input').val('');
            $('#areas-results').hide();
            $('.city-suggestions').hide();
        },
        
        setLoading: function($btn, loading) {
            if (loading) {
                $btn.addClass('loading').prop('disabled', true);
            } else {
                $btn.removeClass('loading').prop('disabled', false);
            }
        },
        
        showNotification: function(message, type = 'info') {
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                warning: '#f59e0b',
                info: '#3b82f6'
            };
            
            // Remove existing notifications
            $('.notification').remove();
            
            const notification = $(`
                <div class="notification notification-${type}" style="
                    position: fixed; top: 24px; right: 24px; z-index: 10000;
                    display: flex; align-items: center; gap: 12px;
                    padding: 16px 20px; border-radius: 8px;
                    background: ${colors[type]}; color: white;
                    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                    font-weight: 500; max-width: 400px;
                    transform: translateX(100%); opacity: 0;
                    transition: all 0.3s ease;
                ">
                    ${message}
                </div>
            `);
            
            $('body').append(notification);
            
            // Animate in
            setTimeout(() => {
                notification.css({
                    transform: 'translateX(0)',
                    opacity: 1
                });
            }, 100);
            
            // Auto remove
            setTimeout(() => {
                notification.css({
                    transform: 'translateX(100%)',
                    opacity: 0
                });
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        },
        
        escapeHtml: function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    // Initialize the enhanced areas manager
    EnhancedAreasManager.init();
    
    // Add modal open/close body class management
    $('body').on('modal-open', function() {
        $('body').css('overflow', 'hidden');
    });
    
    $('body').on('modal-close', function() {
        $('body').css('overflow', '');
    });
});
</script>