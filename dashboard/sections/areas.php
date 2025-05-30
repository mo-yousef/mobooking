<?php
// dashboard/sections/areas.php - Enhanced Service Areas Management with ZIP Code Integration
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

// Countries supported by Zippopotam.us API
$supported_countries = array(
    'US' => 'United States',
    'CA' => 'Canada',
    'GB' => 'United Kingdom',
    'DE' => 'Germany',
    'FR' => 'France',
    'ES' => 'Spain',
    'IT' => 'Italy',
    'NL' => 'Netherlands',
    'BE' => 'Belgium',
    'CH' => 'Switzerland',
    'AT' => 'Austria',
    'SE' => 'Sweden',
    'NO' => 'Norway',
    'DK' => 'Denmark',
    'FI' => 'Finland',
    'PL' => 'Poland',
    'CZ' => 'Czech Republic',
    'HU' => 'Hungary',
    'SK' => 'Slovakia',
    'SI' => 'Slovenia',
    'HR' => 'Croatia',
    'PT' => 'Portugal',
    'IE' => 'Ireland',
    'LU' => 'Luxembourg',
    'MT' => 'Malta',
    'CY' => 'Cyprus',
    'EE' => 'Estonia',
    'LV' => 'Latvia',
    'LT' => 'Lithuania',
    'JP' => 'Japan',
    'AU' => 'Australia',
    'NZ' => 'New Zealand',
    'MX' => 'Mexico',
    'BR' => 'Brazil',
    'AR' => 'Argentina',
    'IN' => 'India',
    'TR' => 'Turkey',
    'RU' => 'Russia',
    'ZA' => 'South Africa'
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
                <p class="areas-subtitle"><?php _e('Manage cities and ZIP codes where you provide services', 'mobooking'); ?></p>
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
                        <span class="stat-number"><?php echo esc_html($supported_countries[$selected_country] ?? $selected_country); ?></span>
                        <span class="stat-label"><?php _e('Country', 'mobooking'); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="areas-header-actions">
            <?php if ($selected_country) : ?>
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
        <!-- Country Selection Step -->
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
                    <p><?php _e('Choose the country where you provide services. You can only select one country per business.', 'mobooking'); ?></p>
                </div>
                
                <div class="country-selection-form">
                    <div class="form-group">
                        <label for="country-select"><?php _e('Country', 'mobooking'); ?></label>
                        <select id="country-select" class="country-dropdown">
                            <option value=""><?php _e('Select a country...', 'mobooking'); ?></option>
                            <?php foreach ($supported_countries as $code => $name) : ?>
                                <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="field-help"><?php _e('This will determine which cities and ZIP codes are available for your service areas.', 'mobooking'); ?></p>
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
        <!-- Empty State for Selected Country -->
        <div class="areas-empty-state">
            <div class="empty-state-visual">
                <div class="empty-state-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>
                <div class="empty-state-sparkles">
                    <div class="sparkle sparkle-1"></div>
                    <div class="sparkle sparkle-2"></div>
                    <div class="sparkle sparkle-3"></div>
                </div>
            </div>
            <div class="empty-state-content">
                <h2><?php printf(__('Add Cities in %s', 'mobooking'), $supported_countries[$selected_country]); ?></h2>
                <p><?php _e('Start by adding the cities where you provide services. We\'ll automatically fetch the ZIP codes for each city.', 'mobooking'); ?></p>
                <button type="button" id="add-first-city-btn" class="btn-primary btn-large">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <?php _e('Add Your First City', 'mobooking'); ?>
                </button>
            </div>
        </div>
    <?php else : ?>
        <!-- Areas Management -->
        <div class="areas-management">
            <!-- Quick Stats & Actions -->
            <div class="areas-toolbar">
                <div class="toolbar-section">
                    <div class="country-indicator">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M2 12h20"/>
                        </svg>
                        <span><?php echo esc_html($supported_countries[$selected_country]); ?></span>
                    </div>
                </div>
                
                <div class="toolbar-section">
                    <div class="bulk-actions">
                        <select id="bulk-action-select">
                            <option value=""><?php _e('Bulk Actions', 'mobooking'); ?></option>
                            <option value="activate"><?php _e('Activate', 'mobooking'); ?></option>
                            <option value="deactivate"><?php _e('Deactivate', 'mobooking'); ?></option>
                            <option value="refresh_zip_codes"><?php _e('Refresh ZIP Codes', 'mobooking'); ?></option>
                            <option value="delete"><?php _e('Delete', 'mobooking'); ?></option>
                        </select>
                        <button type="button" id="apply-bulk-action" class="btn-secondary"><?php _e('Apply', 'mobooking'); ?></button>
                    </div>
                    
                    <div class="areas-search">
                        <input type="text" id="areas-search-input" placeholder="<?php _e('Search cities or ZIP codes...', 'mobooking'); ?>" class="search-input">
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
                    <div class="grid-header-cell city"><?php _e('City/Area', 'mobooking'); ?></div>
                    <div class="grid-header-cell zip-codes"><?php _e('ZIP Codes', 'mobooking'); ?></div>
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
                        $zip_count = count($zip_codes);
                    ?>
                        <div class="area-row enhanced" data-area-id="<?php echo esc_attr($area->id); ?>" data-city="<?php echo esc_attr($area->label ?: $area->zip_code); ?>">
                            <div class="grid-cell select">
                                <input type="checkbox" class="area-checkbox" value="<?php echo esc_attr($area->id); ?>">
                            </div>
                            <div class="grid-cell city">
                                <div class="city-info">
                                    <span class="city-name"><?php echo esc_html($area->label ?: 'Unnamed Area'); ?></span>
                                    <?php if (!empty($area->state)) : ?>
                                        <span class="state-name"><?php echo esc_html($area->state); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="grid-cell zip-codes">
                                <div class="zip-codes-info">
                                    <span class="zip-count"><?php echo sprintf(_n('%d ZIP code', '%d ZIP codes', $zip_count, 'mobooking'), $zip_count); ?></span>
                                    <?php if ($zip_count > 0) : ?>
                                        <div class="zip-preview">
                                            <?php 
                                            $preview_zips = array_slice($zip_codes, 0, 3);
                                            echo esc_html(implode(', ', $preview_zips));
                                            if ($zip_count > 3) {
                                                echo '<span class="zip-more">+' . ($zip_count - 3) . ' more</span>';
                                            }
                                            ?>
                                        </div>
                                    <?php else : ?>
                                        <span class="no-zip-codes"><?php _e('No ZIP codes', 'mobooking'); ?></span>
                                    <?php endif; ?>
                                </div>
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
                                    <button type="button" class="btn-icon view-zip-codes-btn" data-area-id="<?php echo esc_attr($area->id); ?>" title="<?php _e('View ZIP Codes', 'mobooking'); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="btn-icon edit-area-btn" data-area-id="<?php echo esc_attr($area->id); ?>" title="<?php _e('Edit Area', 'mobooking'); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="m18.5 2.5-9.5 9.5L4 15l1-4 9.5-9.5 3 3Z"></path>
                                        </svg>
                                    </button>
                                    <button type="button" class="btn-icon refresh-zip-codes-btn" data-area-id="<?php echo esc_attr($area->id); ?>" title="<?php _e('Refresh ZIP Codes', 'mobooking'); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                                            <path d="M21 3v5h-5"/>
                                            <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                                            <path d="M8 16H3v5"/>
                                        </svg>
                                    </button>
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

<!-- City/Area Modal (Add/Edit) -->
<div id="city-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-content modal-lg">
        <button class="modal-close" aria-label="<?php _e('Close', 'mobooking'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </button>
        
        <h3 id="city-modal-title"><?php _e('Add Service Area', 'mobooking'); ?></h3>
        
        <form id="city-form" method="post">
            <input type="hidden" id="area-id" name="id">
            <input type="hidden" id="area-country" name="country" value="<?php echo esc_attr($selected_country); ?>">
            <?php wp_nonce_field('mobooking-area-nonce', 'nonce'); ?>
            
            <div class="modal-tabs">
                <button type="button" class="tab-btn active" data-tab="basic-info">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <?php _e('Basic Info', 'mobooking'); ?>
                </button>
                <button type="button" class="tab-btn" data-tab="zip-codes">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/>
                    </svg>
                    <?php _e('ZIP Codes', 'mobooking'); ?>
                </button>
            </div>
            
            <!-- Basic Info Tab -->
            <div class="tab-content active" id="basic-info-tab">
                <div class="form-group">
                    <label for="city-name"><?php _e('City/Area Name', 'mobooking'); ?> *</label>
                    <input type="text" id="city-name" name="city_name" class="form-control" required 
                           placeholder="<?php _e('e.g., New York, Los Angeles, Downtown, etc.', 'mobooking'); ?>">
                    <p class="field-help"><?php _e('Enter the city or area name where you provide services', 'mobooking'); ?></p>
                </div>
                
                <div class="form-group">
                    <label for="state-province"><?php _e('State/Province', 'mobooking'); ?></label>
                    <input type="text" id="state-province" name="state" class="form-control" 
                           placeholder="<?php _e('e.g., CA, NY, Texas, etc.', 'mobooking'); ?>">
                    <p class="field-help"><?php _e('Optional: State or province for better organization', 'mobooking'); ?></p>
                </div>
                
                <div class="form-group">
                    <label for="area-description"><?php _e('Description', 'mobooking'); ?></label>
                    <textarea id="area-description" name="description" class="form-control" rows="3" 
                              placeholder="<?php _e('Optional description of this service area...', 'mobooking'); ?>"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="area-active" name="active" value="1" checked>
                        <?php _e('Active (available for booking)', 'mobooking'); ?>
                    </label>
                </div>
                
                <div class="zip-fetch-section">
                    <h4><?php _e('ZIP Code Fetching', 'mobooking'); ?></h4>
                    <p class="section-description"><?php _e('We\'ll automatically fetch ZIP codes for this area using the Zippopotam.us API.', 'mobooking'); ?></p>
                    
                    <button type="button" id="fetch-zip-codes-btn" class="btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                        </svg>
                        <span class="btn-text"><?php _e('Fetch ZIP Codes', 'mobooking'); ?></span>
                        <span class="btn-loading" style="display: none;"><?php _e('Fetching...', 'mobooking'); ?></span>
                    </button>
                    
                    <div id="zip-fetch-result" class="zip-fetch-result"></div>
                </div>
            </div>
            
            <!-- ZIP Codes Tab -->
            <div class="tab-content" id="zip-codes-tab">
                <div class="zip-codes-section">
                    <div class="section-header">
                        <h4><?php _e('ZIP Codes for this Area', 'mobooking'); ?></h4>
                        <button type="button" id="add-custom-zip-btn" class="btn-secondary btn-sm">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            <?php _e('Add Custom ZIP', 'mobooking'); ?>
                        </button>
                    </div>
                    
                    <div id="zip-codes-list" class="zip-codes-list">
                        <div class="no-zip-codes-message">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/>
                            </svg>
                            <p><?php _e('No ZIP codes yet. Use "Fetch ZIP Codes" to automatically get ZIP codes for this area.', 'mobooking'); ?></p>
                        </div>
                    </div>
                </div>
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
                
                <button type="button" id="cancel-city-btn" class="btn-secondary">
                    <?php _e('Cancel', 'mobooking'); ?>
                </button>
                
                <button type="submit" class="btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17,21 17,13 7,13 7,21"/>
                        <polyline points="7,3 7,8 15,8"/>
                    </svg>
                    <span class="btn-text"><?php _e('Save Area', 'mobooking'); ?></span>
                    <span class="btn-loading" style="display: none;"><?php _e('Saving...', 'mobooking'); ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ZIP Codes View Modal -->
<div id="zip-codes-view-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-content modal-lg">
        <button class="modal-close" aria-label="<?php _e('Close', 'mobooking'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </button>
        
        <h3 id="zip-view-modal-title"><?php _e('ZIP Codes', 'mobooking'); ?></h3>
        
        <div class="zip-codes-viewer">
            <div class="zip-viewer-header">
                <div class="zip-search">
                    <input type="text" id="zip-search-input" placeholder="<?php _e('Search ZIP codes...', 'mobooking'); ?>">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                    </svg>
                </div>
                <div class="zip-actions">
                    <button type="button" id="export-zip-codes-btn" class="btn-secondary btn-sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 4v12"/>
                        </svg>
                        <?php _e('Export', 'mobooking'); ?>
                    </button>
                </div>
            </div>
            
            <div id="zip-codes-display" class="zip-codes-display">
                <!-- ZIP codes will be loaded here -->
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

<!-- Enhanced CSS Styles -->
<style>
.enhanced-areas {
    --primary-color: #3b82f6;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
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

/* Country Selection */
.country-selection-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 60vh;
    padding: 2rem;
}

.country-selection-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    padding: 3rem;
    max-width: 500px;
    width: 100%;
    text-align: center;
}

.selection-header {
    margin-bottom: 2rem;
}

.selection-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    color: white;
}

.selection-icon svg {
    width: 32px;
    height: 32px;
}

.country-selection-form .form-group {
    margin-bottom: 1.5rem;
    text-align: left;
}

.country-dropdown {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid var(--gray-200);
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.2s;
}

.country-dropdown:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Enhanced Areas Grid */
.area-row.enhanced {
    transition: all 0.2s;
    border-left: 4px solid transparent;
}

.area-row.enhanced:hover {
    background-color: var(--gray-50);
    border-left-color: var(--primary-color);
}

.city-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.city-name {
    font-weight: 600;
    color: var(--gray-900);
}

.state-name {
    font-size: 0.875rem;
    color: var(--gray-500);
}

.zip-codes-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.zip-count {
    font-weight: 500;
    color: var(--gray-700);
}

.zip-preview {
    font-size: 0.875rem;
    color: var(--gray-500);
    font-family: 'Courier New', monospace;
}

.zip-more {
    color: var(--primary-color);
    font-weight: 500;
}

.no-zip-codes {
    color: var(--warning-color);
    font-style: italic;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-badge.active {
    background-color: #dcfce7;
    color: #166534;
}

.status-badge.inactive {
    background-color: #fef3c7;
    color: #92400e;
}

.status-badge svg {
    width: 16px;
    height: 16px;
}

/* Country Indicator */
.country-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--gray-100);
    border-radius: 6px;
    font-weight: 500;
    color: var(--gray-700);
}

.country-indicator svg {
    width: 18px;
    height: 18px;
}

/* Modal Enhancements */
.modal-lg {
    max-width: 800px;
}

.modal-tabs {
    display: flex;
    border-bottom: 2px solid var(--gray-200);
    margin-bottom: 1.5rem;
}

.tab-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: none;
    border: none;
    color: var(--gray-500);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border-bottom: 2px solid transparent;
}

.tab-btn:hover {
    color: var(--gray-700);
    background-color: var(--gray-50);
}

.tab-btn.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.tab-btn svg {
    width: 18px;
    height: 18px;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* ZIP Codes Section */
.zip-fetch-section {
    background: var(--gray-50);
    border-radius: 8px;
    padding: 1.5rem;
    margin-top: 1.5rem;
}

.zip-fetch-section h4 {
    margin: 0 0 0.5rem 0;
    color: var(--gray-900);
}

.section-description {
    color: var(--gray-600);
    margin-bottom: 1rem;
}

.zip-fetch-result {
    margin-top: 1rem;
    padding: 1rem;
    border-radius: 6px;
    display: none;
}

.zip-fetch-result.success {
    background-color: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
    display: block;
}

.zip-fetch-result.error {
    background-color: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    display: block;
}

.zip-codes-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid var(--gray-200);
    border-radius: 6px;
    background: white;
}

.zip-code-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-100);
}

.zip-code-item:last-child {
    border-bottom: none;
}

.zip-code-value {
    font-family: 'Courier New', monospace;
    font-weight: 500;
    color: var(--gray-900);
}

.zip-code-actions {
    display: flex;
    gap: 0.5rem;
}

.remove-zip-btn {
    padding: 0.25rem;
    background: none;
    border: none;
    color: var(--danger-color);
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.remove-zip-btn:hover {
    background-color: var(--gray-100);
}

.remove-zip-btn svg {
    width: 16px;
    height: 16px;
}

.no-zip-codes-message {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    color: var(--gray-500);
    text-align: center;
}

.no-zip-codes-message svg {
    width: 48px;
    height: 48px;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* ZIP Codes Viewer */
.zip-codes-viewer {
    max-height: 70vh;
    display: flex;
    flex-direction: column;
}

.zip-viewer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--gray-200);
}

.zip-search {
    position: relative;
    flex: 1;
    max-width: 300px;
}

.zip-search input {
    width: 100%;
    padding: 0.5rem 2.5rem 0.5rem 1rem;
    border: 1px solid var(--gray-300);
    border-radius: 6px;
    font-size: 0.875rem;
}

.zip-search .search-icon {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
    color: var(--gray-400);
}

.zip-codes-display {
    flex: 1;
    overflow-y: auto;
    border: 1px solid var(--gray-200);
    border-radius: 6px;
    background: white;
}

.zip-codes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 0.5rem;
    padding: 1rem;
}

.zip-code-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: 6px;
    font-family: 'Courier New', monospace;
    font-weight: 500;
    color: var(--gray-700);
    transition: all 0.2s;
}

.zip-code-badge:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

/* Confirmation Modal */
.confirmation-content {
    text-align: center;
    padding: 1rem 0;
}

.warning-icon {
    width: 64px;
    height: 64px;
    background: var(--warning-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: white;
}

.warning-icon svg {
    width: 32px;
    height: 32px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .country-selection-card {
        padding: 2rem 1.5rem;
    }
    
    .areas-toolbar {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .toolbar-section {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .areas-grid-header {
        display: none;
    }
    
    .area-row.enhanced {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 1rem;
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        margin-bottom: 0.75rem;
    }
    
    .grid-cell {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .grid-cell::before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--gray-700);
        min-width: 100px;
    }
    
    .modal-lg {
        max-width: 95vw;
        margin: 1rem;
    }
    
    .modal-tabs {
        overflow-x: auto;
        white-space: nowrap;
    }
    
    .tab-btn {
        flex-shrink: 0;
    }
    
    .zip-viewer-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .zip-codes-grid {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    }
}

@media (max-width: 480px) {
    .country-selection-card {
        padding: 1.5rem 1rem;
    }
    
    .zip-codes-grid {
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    }
}

/* Loading States */
.btn-loading {
    display: none;
}

.loading .btn-text {
    display: none;
}

.loading .btn-loading {
    display: inline-flex;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.area-row.enhanced {
    animation: fadeIn 0.3s ease-out;
}

.zip-code-badge {
    animation: fadeIn 0.2s ease-out;
}

/* Focus Styles */
.btn-primary:focus,
.btn-secondary:focus,
.btn-danger:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
</style>

<script>
// Enhanced Areas Management JavaScript with ZIP Code Integration
jQuery(document).ready(function($) {
    const EnhancedAreasManager = {
        config: {
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('mobooking-area-nonce'); ?>',
            userId: <?php echo $user_id; ?>,
            selectedCountry: '<?php echo esc_js($selected_country); ?>',
            zippopotamApiUrl: 'https://api.zippopotam.us/'
        },
        
        state: {
            isProcessing: false,
            currentAreaId: null,
            selectedAreas: [],
            fetchedZipCodes: [],
            currentTab: 'basic-info'
        },
        
        init: function() {
            console.log('🚀 Enhanced Areas Manager initializing...');
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
                    $('#confirm-country-btn').find('.btn-text').text('Confirm ' + $(this).find('option:selected').text());
                }
            });
            
            $('#select-country-btn, #confirm-country-btn').on('click', function() {
                self.showCountryConfirmation();
            });
            
            $('#change-country-btn').on('click', function() {
                self.changeCountry();
            });
            
            // City/Area management
            $('#add-city-btn, #add-first-city-btn').on('click', function() {
                self.showAddCityModal();
            });
            
            $(document).on('click', '.edit-area-btn', function() {
                const areaId = $(this).data('area-id');
                self.editArea(areaId);
            });
            
            $(document).on('click', '.view-zip-codes-btn', function() {
                const areaId = $(this).data('area-id');
                self.viewZipCodes(areaId);
            });
            
            $(document).on('click', '.refresh-zip-codes-btn', function() {
                const areaId = $(this).data('area-id');
                self.refreshZipCodes(areaId);
            });
            
            $(document).on('click', '.toggle-area-btn', function() {
                const areaId = $(this).data('area-id');
                const isActive = $(this).data('active') === 1;
                self.toggleAreaStatus(areaId, !isActive);
            });
            
            $(document).on('click', '.delete-area-btn', function() {
                const areaId = $(this).data('area-id');
                self.deleteArea(areaId);
            });
            
            // City form submission
            $('#city-form').on('submit', function(e) {
                e.preventDefault();
                self.saveArea();
            });
            
            // ZIP code fetching
            $('#fetch-zip-codes-btn').on('click', function() {
                self.fetchZipCodes();
            });
            
            // Modal tab switching
            $('.tab-btn').on('click', function() {
                const tabId = $(this).data('tab');
                self.switchTab(tabId);
            });
            
            // ZIP codes management
            $('#add-custom-zip-btn').on('click', function() {
                self.addCustomZipCode();
            });
            
            $(document).on('click', '.remove-zip-btn', function() {
                $(this).closest('.zip-code-item').remove();
                self.updateZipCodesList();
            });
            
            // Modal controls
            $('.modal-close, #cancel-city-btn, .cancel-action-btn, .cancel-country-btn').on('click', function() {
                self.hideModals();
            });
            
            // Confirmation actions
            $('.confirm-country-selection-btn').on('click', function() {
                self.confirmCountrySelection();
            });
            
            $('.confirm-action-btn').on('click', function() {
                self.executeConfirmedAction();
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
            
            $('#zip-search-input').on('input', function() {
                self.filterZipCodes($(this).val());
            });
            
            // Export ZIP codes
            $('#export-zip-codes-btn').on('click', function() {
                self.exportZipCodes();
            });
        },
        
        initializeComponents: function() {
            // Initialize any components that need setup
            this.updateAreaCount();
        },
        
        showCountryConfirmation: function() {
            const selectedCountry = $('#country-select').val();
            if (!selectedCountry) {
                this.showNotification('Please select a country first', 'warning');
                return;
            }
            
            const countryName = $('#country-select option:selected').text();
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
        
        showAddCityModal: function() {
            this.state.currentAreaId = null;
            
            // Reset form
            $('#city-form')[0].reset();
            $('#area-id').val('');
            $('#area-active').prop('checked', true);
            
            // Clear ZIP codes
            this.clearZipCodesList();
            this.hideZipFetchResult();
            
            // Reset tabs
            this.switchTab('basic-info');
            
            // Update modal
            $('#city-modal-title').text('<?php _e('Add Service Area', 'mobooking'); ?>');
            $('#delete-area-btn').hide();
            
            this.showModal('#city-modal');
        },
        
        editArea: function(areaId) {
            this.state.currentAreaId = areaId;
            
            const data = {
                action: 'mobooking_get_area_details',
                id: areaId,
                nonce: this.config.nonce
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success && response.data.area) {
                        this.populateAreaForm(response.data.area);
                        $('#city-modal-title').text('<?php _e('Edit Service Area', 'mobooking'); ?>');
                        $('#delete-area-btn').show();
                        this.showModal('#city-modal');
                    } else {
                        this.showNotification('Error loading area details', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Error loading area details', 'error');
                }
            });
        },
        
        populateAreaForm: function(area) {
            $('#area-id').val(area.id);
            $('#city-name').val(area.label || area.city_name);
            $('#state-province').val(area.state || '');
            $('#area-description').val(area.description || '');
            $('#area-active').prop('checked', area.active == 1);
            
            // Load ZIP codes if available
            if (area.zip_codes) {
                const zipCodes = typeof area.zip_codes === 'string' 
                    ? JSON.parse(area.zip_codes) 
                    : area.zip_codes;
                this.populateZipCodesList(zipCodes);
            } else if (area.zip_code) {
                // Legacy single ZIP code
                this.populateZipCodesList([area.zip_code]);
            }
        },
        
        fetchZipCodes: function() {
            const cityName = $('#city-name').val().trim();
            const stateName = $('#state-province').val().trim();
            
            if (!cityName) {
                this.showNotification('Please enter a city name first', 'warning');
                return;
            }
            
            if (this.state.isProcessing) return;
            
            this.state.isProcessing = true;
            const $btn = $('#fetch-zip-codes-btn');
            this.setLoading($btn, true);
            this.hideZipFetchResult();
            
            // Build API URL
            let apiUrl = this.config.zippopotamApiUrl + this.config.selectedCountry + '/' + encodeURIComponent(cityName);
            
            // Add state if provided (for US)
            if (stateName && this.config.selectedCountry === 'US') {
                apiUrl += '/' + encodeURIComponent(stateName);
            }
            
            console.log('Fetching ZIP codes from:', apiUrl);
            
            $.ajax({
                url: apiUrl,
                type: 'GET',
                dataType: 'json',
                success: (response) => {
                    console.log('ZIP API Response:', response);
                    this.processZipCodeResponse(response);
                },
                error: (xhr, status, error) => {
                    console.error('ZIP API Error:', xhr.status, error);
                    let errorMessage = 'Failed to fetch ZIP codes. ';
                    
                    if (xhr.status === 404) {
                        errorMessage += 'City not found. Please check the city name and try again.';
                    } else if (xhr.status === 0) {
                        errorMessage += 'Network error. Please check your internet connection.';
                    } else {
                        errorMessage += 'Please try again later.';
                    }
                    
                    this.showZipFetchResult(errorMessage, 'error');
                },
                complete: () => {
                    this.state.isProcessing = false;
                    this.setLoading($btn, false);
                }
            });
        },
        
        processZipCodeResponse: function(response) {
            let zipCodes = [];
            
            try {
                if (response.places && Array.isArray(response.places)) {
                    // Extract ZIP codes from places
                    zipCodes = response.places.map(place => place['post code']).filter(Boolean);
                    
                    // Remove duplicates and sort
                    zipCodes = [...new Set(zipCodes)].sort();
                    
                    if (zipCodes.length > 0) {
                        this.state.fetchedZipCodes = zipCodes;
                        this.populateZipCodesList(zipCodes);
                        
                        const message = `Successfully fetched ${zipCodes.length} ZIP code(s) for ${response['place name']}, ${response.country}.`;
                        this.showZipFetchResult(message, 'success');
                        
                        // Switch to ZIP codes tab to show results
                        this.switchTab('zip-codes');
                    } else {
                        this.showZipFetchResult('No ZIP codes found for this location.', 'error');
                    }
                } else {
                    this.showZipFetchResult('Invalid response format from ZIP code service.', 'error');
                }
            } catch (error) {
                console.error('Error processing ZIP response:', error);
                this.showZipFetchResult('Error processing ZIP code data.', 'error');
            }
        },
        
        populateZipCodesList: function(zipCodes) {
            const $container = $('#zip-codes-list');
            $container.empty();
            
            if (!zipCodes || zipCodes.length === 0) {
                $container.html(this.getNoZipCodesMessage());
                return;
            }
            
            zipCodes.forEach(zipCode => {
                const zipItem = this.createZipCodeItem(zipCode);
                $container.append(zipItem);
            });
            
            this.updateZipCodesList();
        },
        
        createZipCodeItem: function(zipCode) {
            return $(`
                <div class="zip-code-item">
                    <span class="zip-code-value">${this.escapeHtml(zipCode)}</span>
                    <div class="zip-code-actions">
                        <button type="button" class="remove-zip-btn" title="Remove this ZIP code">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 6 6 18M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `);
        },
        
        getNoZipCodesMessage: function() {
            return `
                <div class="no-zip-codes-message">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/>
                    </svg>
                    <p><?php _e('No ZIP codes yet. Use "Fetch ZIP Codes" to automatically get ZIP codes for this area.', 'mobooking'); ?></p>
                </div>
            `;
        },
        
        addCustomZipCode: function() {
            const zipCode = prompt('<?php _e('Enter ZIP code:', 'mobooking'); ?>');
            
            if (zipCode && zipCode.trim()) {
                const trimmedZip = zipCode.trim();
                
                // Check if ZIP already exists
                const exists = $('#zip-codes-list .zip-code-value').filter(function() {
                    return $(this).text() === trimmedZip;
                }).length > 0;
                
                if (exists) {
                    this.showNotification('This ZIP code already exists', 'warning');
                    return;
                }
                
                // Remove no-codes message if present
                $('#zip-codes-list .no-zip-codes-message').remove();
                
                // Add the new ZIP code
                const zipItem = this.createZipCodeItem(trimmedZip);
                $('#zip-codes-list').append(zipItem);
                
                this.updateZipCodesList();
                this.showNotification('ZIP code added successfully', 'success');
            }
        },
        
        updateZipCodesList: function() {
            // This function can be used to update any counters or validations
            const zipCount = $('#zip-codes-list .zip-code-item').length;
            
            if (zipCount === 0) {
                $('#zip-codes-list').html(this.getNoZipCodesMessage());
            }
        },
        
        clearZipCodesList: function() {
            $('#zip-codes-list').html(this.getNoZipCodesMessage());
            this.state.fetchedZipCodes = [];
        },
        
        saveArea: function() {
            if (this.state.isProcessing) return;
            
            // Validate form
            const cityName = $('#city-name').val().trim();
            if (!cityName) {
                this.showNotification('City name is required', 'error');
                this.switchTab('basic-info');
                $('#city-name').focus();
                return;
            }
            
            this.state.isProcessing = true;
            const $btn = $('#city-form button[type="submit"]');
            this.setLoading($btn, true);
            
            // Collect ZIP codes
            const zipCodes = [];
            $('#zip-codes-list .zip-code-value').each(function() {
                zipCodes.push($(this).text());
            });
            
            // Prepare form data
            const formData = new FormData($('#city-form')[0]);
            formData.append('action', 'mobooking_save_area_with_zips');
            formData.append('zip_codes', JSON.stringify(zipCodes));
            
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
                        this.refreshAreasTable();
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
        
        viewZipCodes: function(areaId) {
            const data = {
                action: 'mobooking_get_area_zip_codes',
                id: areaId,
                nonce: this.config.nonce
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success && response.data.area) {
                        this.displayZipCodesModal(response.data.area);
                    } else {
                        this.showNotification('Error loading ZIP codes', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Error loading ZIP codes', 'error');
                }
            });
        },
        
        displayZipCodesModal: function(area) {
            const zipCodes = area.zip_codes ? 
                (typeof area.zip_codes === 'string' ? JSON.parse(area.zip_codes) : area.zip_codes) :
                (area.zip_code ? [area.zip_code] : []);
            
            $('#zip-view-modal-title').text(`ZIP Codes for ${area.label || area.city_name}`);
            
            const $display = $('#zip-codes-display');
            $display.empty();
            
            if (zipCodes.length === 0) {
                $display.html(`
                    <div class="no-zip-codes-message">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/>
                        </svg>
                        <p>No ZIP codes available for this area.</p>
                    </div>
                `);
            } else {
                const zipGrid = $('<div class="zip-codes-grid"></div>');
                zipCodes.forEach(zipCode => {
                    zipGrid.append(`<div class="zip-code-badge">${this.escapeHtml(zipCode)}</div>`);
                });
                $display.append(zipGrid);
            }
            
            this.showModal('#zip-codes-view-modal');
        },
        
        refreshZipCodes: function(areaId) {
            if (this.state.isProcessing) return;
            
            const $btn = $(`.refresh-zip-codes-btn[data-area-id="${areaId}"]`);
            $btn.addClass('loading');
            
            const data = {
                action: 'mobooking_refresh_area_zip_codes',
                id: areaId,
                nonce: this.config.nonce
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        this.refreshAreasTable();
                    } else {
                        this.showNotification(response.data?.message || 'Failed to refresh ZIP codes', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Error refreshing ZIP codes', 'error');
                },
                complete: () => {
                    $btn.removeClass('loading');
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
                        this.showNotification(response.data.message, 'success');
                        this.refreshAreasTable();
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
            const cityName = $row.find('.city-name').text();
            
            const message = `<?php _e('Are you sure you want to delete the service area "[CITY]"? This will also remove all associated ZIP codes. This action cannot be undone.', 'mobooking'); ?>`;
            $('#confirmation-message').text(message.replace('[CITY]', cityName));
            
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
                        this.refreshAreasTable();
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
                        this.refreshAreasTable();
                        
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
                const cityName = $row.find('.city-name').text().toLowerCase();
                const stateName = $row.find('.state-name').text().toLowerCase();
                const zipCodes = $row.find('.zip-preview').text().toLowerCase();
                
                const matches = cityName.includes(searchTerm) || 
                              stateName.includes(searchTerm) || 
                              zipCodes.includes(searchTerm);
                
                $row.toggle(matches);
            });
        },
        
        filterZipCodes: function(searchTerm) {
            const $badges = $('.zip-code-badge');
            
            if (!searchTerm) {
                $badges.show();
                return;
            }
            
            searchTerm = searchTerm.toLowerCase();
            
            $badges.each(function() {
                const zipCode = $(this).text().toLowerCase();
                $(this).toggle(zipCode.includes(searchTerm));
            });
        },
        
        exportZipCodes: function() {
            // This could export ZIP codes for the current area being viewed
            this.showNotification('Export functionality will be implemented', 'info');
        },
        
        refreshAreasTable: function() {
            // Reload the page to refresh the areas table
            // In a more sophisticated implementation, this could be done via AJAX
            location.reload();
        },
        
        updateAreaCount: function() {
            const totalAreas = $('.area-row').length;
            const activeAreas = $('.area-row .status-badge.active').length;
            
            // Update stats if they exist
            $('.stat-item .stat-number').first().text(totalAreas);
            $('.stat-item .stat-number').eq(1).text(activeAreas);
        },
        
        switchTab: function(tabId) {
            $('.tab-btn').removeClass('active');
            $(`.tab-btn[data-tab="${tabId}"]`).addClass('active');
            
            $('.tab-content').removeClass('active');
            $(`#${tabId}-tab`).addClass('active');
            
            this.state.currentTab = tabId;
        },
        
        showZipFetchResult: function(message, type) {
            const $result = $('#zip-fetch-result');
            $result.removeClass('success error').addClass(type);
            $result.text(message).show();
        },
        
        hideZipFetchResult: function() {
            $('#zip-fetch-result').hide();
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
            }, 4000);
        },
        
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    // Initialize the enhanced areas manager
    EnhancedAreasManager.init();
});
</script>

<?php
// Add the enhanced AJAX handlers to your Geography Manager class
// These should be added to classes/Geography/Manager.php

/*
 * Additional AJAX Handlers needed for the enhanced functionality:
 * 
 * 1. mobooking_set_service_country - Save selected country
 * 2. mobooking_reset_service_country - Reset country selection  
 * 3. mobooking_save_area_with_zips - Save area with ZIP codes
 * 4. mobooking_get_area_details - Get area details for editing
 * 5. mobooking_get_area_zip_codes - Get ZIP codes for viewing
 * 6. mobooking_refresh_area_zip_codes - Refresh ZIP codes from API
 * 7. mobooking_bulk_area_action - Handle bulk actions
 * 
 * These handlers should:
 * - Validate user permissions
 * - Process the data appropriately
 * - Update the enhanced database structure (with city_name, state, description, zip_codes JSON field)
 * - Return appropriate success/error responses
 * 
 * The database table should be enhanced to include:
 * - city_name (varchar 255)
 * - state (varchar 100) 
 * - description (text)
 * - zip_codes (longtext) - JSON array of ZIP codes
 * - country (varchar 10) - country code
 */
?>