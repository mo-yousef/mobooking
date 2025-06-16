<?php
// dashboard/sections/areas.php - Enhanced Service Areas Management with Local JSON Data
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

// Updated supported countries with local JSON data indicators
$supported_countries = $geography_manager->get_supported_countries();

$script_data = $geography_manager->get_areas_script_data();
?>
<script type="text/javascript">
    window.mobooking_area_vars = <?php echo json_encode($script_data); ?>;
    window.ajax_object = window.mobooking_area_vars; // Define ajax_object immediately
    // mobooking_current_country is part of mobooking_area_vars now, so no separate global needed unless specifically used outside this object.
    // If window.mobooking_current_country is used directly elsewhere, it can be set:
    // window.mobooking_current_country = <?php echo json_encode($script_data['current_country']); ?>;
    // However, it's better to access it via window.mobooking_area_vars.current_country
</script>

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
                <p class="areas-subtitle"><?php _e('Manage your service coverage areas with real ZIP code data', 'mobooking'); ?></p>
            </div>

            <div class="areas-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $total_areas; ?></span>
                    <span class="stat-label"><?php _e('Total Areas', 'mobooking'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $active_areas; ?></span>
                    <span class="stat-label"><?php _e('Active Areas', 'mobooking'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($selected_country)) : ?>
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
                    <p><?php _e('Choose your primary service country to access comprehensive area and ZIP code data. Nordic countries (Sweden, Norway, Denmark, Finland) use local high-quality data, while other countries use external APIs.', 'mobooking'); ?></p>
                </div>

                <div class="country-selection-form">
                    <div class="form-group">
                        <label for="country-select"><?php _e('Country', 'mobooking'); ?></label>
                        <select id="country-select" class="country-dropdown">
                            <option value=""><?php _e('Select a country...', 'mobooking'); ?></option>
                            <?php foreach ($supported_countries as $code => $info) : ?>
                                <option value="<?php echo esc_attr($code); ?>"
                                        data-has-local-data="<?php echo isset($info['has_local_data']) && $info['has_local_data'] ? '1' : '0'; ?>"
                                        data-has-api="<?php echo isset($info['has_api']) && $info['has_api'] ? '1' : '0'; ?>">
                                    <?php echo esc_html($info['name']); ?>
                                    <?php if (isset($info['has_local_data']) && $info['has_local_data']) : ?>
                                        <span class="data-indicator">🏠</span>
                                    <?php elseif (isset($info['has_api']) && $info['has_api']) : ?>
                                        <span class="data-indicator">🌐</span>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="field-help">
                            <?php _e('🏠 = Local high-quality data | 🌐 = External API data', 'mobooking'); ?>
                        </p>
                    </div>

                    <div class="data-source-info" id="data-source-info" style="display: none;">
                        <div class="info-card">
                            <h4 id="data-source-title"></h4>
                            <p id="data-source-description"></p>
                        </div>
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

                    <?php if (isset($supported_countries[$selected_country]['has_local_data']) && $supported_countries[$selected_country]['has_local_data']) : ?>
                        <p><?php _e('Enter a city name and we\'ll fetch all available areas and neighborhoods with ZIP codes from our comprehensive local database.', 'mobooking'); ?></p>
                        <div class="data-quality-badge local-data">
                            <span class="badge-icon">🏠</span>
                            <span class="badge-text"><?php _e('High-Quality Local Data', 'mobooking'); ?></span>
                        </div>
                    <?php else : ?>
                        <p><?php _e('Enter a city name and we\'ll fetch all available areas and neighborhoods with ZIP codes from external data sources.', 'mobooking'); ?></p>
                        <div class="data-quality-badge api-data">
                            <span class="badge-icon">🌐</span>
                            <span class="badge-text"><?php _e('External API Data', 'mobooking'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="city-input-form">
                    <div class="form-group">
                        <label for="city-search"><?php _e('City Name', 'mobooking'); ?></label>
                        <div class="search-input-group">
                            <input type="text" id="city-search" class="city-search-input"
                                   placeholder="<?php _e('Enter city name...', 'mobooking'); ?>" autocomplete="off">
                            <button type="button" id="search-city-btn" class="search-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"/>
                                    <path d="m21 21-4.35-4.35"/>
                                </svg>
                            </button>
                        </div>
                        <div id="city-suggestions" class="city-suggestions" style="display: none;"></div>
                    </div>

                    <div class="search-status" id="search-status" style="display: none;">
                        <div class="status-content">
                            <div class="status-spinner"></div>
                            <span class="status-text"><?php _e('Searching for areas...', 'mobooking'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="country-actions">
                    <button type="button" id="change-country-btn" class="btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        <?php _e('Change Country', 'mobooking'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Areas Results -->
        <div id="areas-results" class="areas-results" style="display: none;">
            <div class="results-header">
                <h3><?php _e('Select Areas to Add', 'mobooking'); ?></h3>
                <p class="results-subtitle"></p>
            </div>

            <div class="areas-grid" id="areas-grid">
                <!-- Area items will be populated here -->
            </div>

            <div class="selection-actions">
                <div class="selection-info">
                    <span id="selected-count">0</span> <?php _e('areas selected', 'mobooking'); ?>
                </div>
                <div class="action-buttons">
                    <button type="button" id="select-all-btn" class="btn-outline">
                        <?php _e('Select All', 'mobooking'); ?>
                    </button>
                    <button type="button" id="save-selected-btn" class="btn-primary" disabled>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17,21 17,13 7,13 7,21"/>
                            <polyline points="7,3 7,8 15,8"/>
                        </svg>
                        <?php _e('Save Selected Areas', 'mobooking'); ?>
                    </button>
                </div>
            </div>
        </div>
    <?php else : ?>
        <!-- Step 3: Manage Existing Areas -->
        <div class="areas-management">
            <div class="management-header">
                <div class="header-content">
                    <h2><?php printf(__('Service Areas in %s', 'mobooking'), $supported_countries[$selected_country]['name']); ?></h2>
                    <div class="data-source-indicator">
                        <?php if (isset($supported_countries[$selected_country]['has_local_data']) && $supported_countries[$selected_country]['has_local_data']) : ?>
                            <span class="indicator local-data">
                                <span class="indicator-icon">🏠</span>
                                <?php _e('Local Data', 'mobooking'); ?>
                            </span>
                        <?php else : ?>
                            <span class="indicator api-data">
                                <span class="indicator-icon">🌐</span>
                                <?php _e('API Data', 'mobooking'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="header-actions">
                    <button type="button" id="add-more-areas-btn" class="btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="16"/>
                            <line x1="8" y1="12" x2="16" y2="12"/>
                        </svg>
                        <?php _e('Add More Areas', 'mobooking'); ?>
                    </button>
                    <button type="button" id="change-country-manage-btn" class="btn-outline">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        </svg>
                        <?php _e('Change Country', 'mobooking'); ?>
                    </button>
                </div>
            </div>

            <div class="areas-table-container">
                <table class="areas-table">
                    <thead>
                        <tr>
                            <th class="checkbox-col">
                                <input type="checkbox" id="select-all-areas">
                            </th>
                            <th class="area-name-col"><?php _e('Area Name', 'mobooking'); ?></th>
                            <th class="zip-codes-col"><?php _e('ZIP Codes', 'mobooking'); ?></th>
                            <th class="state-col"><?php _e('State/Region', 'mobooking'); ?></th>
                            <th class="status-col"><?php _e('Status', 'mobooking'); ?></th>
                            <th class="actions-col"><?php _e('Actions', 'mobooking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($areas as $area) : ?>
                            <tr class="area-row" data-area-id="<?php echo $area->id; ?>">
                                <td class="checkbox-col">
                                    <input type="checkbox" class="area-checkbox" value="<?php echo $area->id; ?>">
                                </td>
                                <td class="area-name-col">
                                    <div class="area-name">
                                        <strong><?php echo esc_html($area->area_name); ?></strong>
                                        <?php if (!empty($area->state)) : ?>
                                            <span class="area-state"><?php echo esc_html($area->state); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="zip-codes-col">
                                    <span class="zip-codes-display"><?php echo esc_html($area->zip_codes); ?></span>
                                </td>
                                <td class="state-col">
                                    <?php echo esc_html($area->state ?: '—'); ?>
                                </td>
                                <td class="status-col">
                                    <label class="status-toggle">
                                        <input type="checkbox" class="area-status-toggle"
                                               data-area-id="<?php echo $area->id; ?>"
                                               <?php checked($area->active, 1); ?>>
                                        <span class="toggle-slider"></span>
                                        <span class="toggle-label">
                                            <?php echo $area->active ? __('Active', 'mobooking') : __('Inactive', 'mobooking'); ?>
                                        </span>
                                    </label>
                                </td>
                                <td class="actions-col">
                                    <div class="action-buttons">
                                        <button type="button" class="btn-icon edit-area-btn"
                                                data-area-id="<?php echo $area->id; ?>"
                                                title="<?php _e('Edit Area', 'mobooking'); ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="m18.5 2.5 a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </button>
                                        <button type="button" class="btn-icon delete-area-btn"
                                                data-area-id="<?php echo $area->id; ?>"
                                                title="<?php _e('Delete Area', 'mobooking'); ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3,6 5,6 21,6"/>
                                                <path d="m19,6v14a2,2 0 0,1-2,2H7a2,2 0 0,1-2-2V6m3,0V4a2,2 0 0,1,2-2h4a2,2 0 0,1,2,2v2"/>
                                                <line x1="10" y1="11" x2="10" y2="17"/>
                                                <line x1="14" y1="11" x2="14" y2="17"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="bulk-actions">
                <div class="bulk-actions-left">
                    <select id="bulk-action-select">
                        <option value=""><?php _e('Bulk Actions', 'mobooking'); ?></option>
                        <option value="activate"><?php _e('Activate Selected', 'mobooking'); ?></option>
                        <option value="deactivate"><?php _e('Deactivate Selected', 'mobooking'); ?></option>
                        <option value="delete"><?php _e('Delete Selected', 'mobooking'); ?></option>
                    </select>
                    <button type="button" id="apply-bulk-action-btn" class="btn-secondary" disabled>
                        <?php _e('Apply', 'mobooking'); ?>
                    </button>
                </div>

                <div class="bulk-actions-right">
                    <span class="areas-count">
                        <?php printf(__('Showing %d of %d areas', 'mobooking'), count($areas), $total_areas); ?>
                    </span>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Enhanced JavaScript for Local JSON Support -->

<script>
// Complete JavaScript for dashboard/sections/areas.php with Local JSON Support
document.addEventListener('DOMContentLoaded', function() {
    /*
    if (typeof window.mobooking_area_vars === 'undefined') { ... } // REMOVE THIS BLOCK
    window.ajax_object = window.mobooking_area_vars; // This is now done in the script tag above
    */

    const EnhancedAreasManager = {
        // Configuration
        selectedAreas: new Set(),
        isLoading: false,

        // Initialize the manager
        init: function() {
            this.bindEvents();
            this.setupDataSourceInfo();
            this.initializeExistingElements();

            console.log('Enhanced Areas Manager initialized');
        },

        // Bind all event listeners
        bindEvents: function() {
            // Country selection
            const countrySelect = document.getElementById('country-select');
            const confirmBtn = document.getElementById('confirm-country-btn');

            if (countrySelect) {
                countrySelect.addEventListener('change', this.handleCountryChange.bind(this));
            }

            if (confirmBtn) {
                confirmBtn.addEventListener('click', this.confirmCountrySelection.bind(this));
            }

            // City search
            const citySearch = document.getElementById('city-search');
            const searchBtn = document.getElementById('search-city-btn');

            if (citySearch) {
                citySearch.addEventListener('input', this.handleCityInput.bind(this));
                citySearch.addEventListener('keypress', this.handleCityKeypress.bind(this));
            }

            if (searchBtn) {
                searchBtn.addEventListener('click', this.searchCityAreas.bind(this));
            }

            // Area selection and actions
            document.addEventListener('click', this.handleDocumentClick.bind(this));
            document.addEventListener('change', this.handleDocumentChange.bind(this));

            // Save selected areas
            const saveBtn = document.getElementById('save-selected-btn');
            if (saveBtn) {
                saveBtn.addEventListener('click', this.saveSelectedAreas.bind(this));
            }

            // Select all areas
            const selectAllBtn = document.getElementById('select-all-btn');
            if (selectAllBtn) {
                selectAllBtn.addEventListener('click', this.toggleSelectAll.bind(this));
            }

            // Change country buttons
            const changeCountryBtns = document.querySelectorAll('#change-country-btn, #change-country-manage-btn');
            changeCountryBtns.forEach(btn => {
                btn.addEventListener('click', this.resetCountrySelection.bind(this));
            });

            // Add more areas button
            const addMoreBtn = document.getElementById('add-more-areas-btn');
            if (addMoreBtn) {
                addMoreBtn.addEventListener('click', this.showAddMoreAreas.bind(this));
            }

            // Area management events
            this.bindAreaManagementEvents();
        },

        // Handle document-level click events
        handleDocumentClick: function(e) {
            // Area checkbox selection
            if (e.target.matches('.area-checkbox')) {
                this.handleAreaCheckboxChange(e.target);
            }

            // Area item clicks (to select checkbox)
            if (e.target.closest('.area-item') && !e.target.matches('input')) {
                const areaItem = e.target.closest('.area-item');
                const checkbox = areaItem.querySelector('.area-checkbox');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    this.handleAreaCheckboxChange(checkbox);
                }
            }

            // Delete area buttons
            if (e.target.closest('.delete-area-btn')) {
                e.preventDefault();
                this.handleDeleteArea(e);
            }

            // Edit area buttons
            if (e.target.closest('.edit-area-btn')) {
                e.preventDefault();
                this.handleEditArea(e);
            }

            // Bulk action apply button
            if (e.target.matches('#apply-bulk-action-btn')) {
                e.preventDefault();
                this.handleBulkAction();
            }

            // Close notification
            if (e.target.matches('.notification-close')) {
                e.target.closest('.notification').remove();
            }
        },

        // Handle document-level change events
        handleDocumentChange: function(e) {
            // Area status toggles
            if (e.target.matches('.area-status-toggle')) {
                this.handleStatusToggle(e);
            }

            // Select all areas checkbox
            if (e.target.matches('#select-all-areas')) {
                this.handleSelectAllAreas(e.target.checked);
            }

            // Individual area checkboxes in management table
            if (e.target.matches('.area-checkbox')) {
                this.updateBulkActionState();
            }

            // Bulk action select dropdown
            if (e.target.matches('#bulk-action-select')) {
                this.updateBulkActionState();
            }
        },

        // Setup data source information
        setupDataSourceInfo: function() {
            this.dataSourceDescriptions = {
                local_data: {
                    title: 'High-Quality Local Data',
                    description: 'This country uses our comprehensive local database with accurate ZIP codes and area information. Data is stored locally for fast access and high reliability.'
                },
                api_data: {
                    title: 'External API Data',
                    description: 'This country uses data from external APIs including Zippopotam and GeoNames. Data quality is good but depends on external service availability.'
                }
            };
        },

        // Initialize existing elements
        initializeExistingElements: function() {
            // Update selection count if area results are already visible
            if (document.getElementById('areas-results') && document.getElementById('areas-results').style.display !== 'none') {
                this.updateSelectionCount();
            }

            // Update bulk action state if in management view
            if (document.querySelector('.areas-management')) {
                this.updateBulkActionState();
            }
        },

        // Handle country selection change
        handleCountryChange: function(e) {
            const countrySelect = e.target;
            const confirmBtn = document.getElementById('confirm-country-btn');
            const infoDiv = document.getElementById('data-source-info');
            const titleEl = document.getElementById('data-source-title');
            const descEl = document.getElementById('data-source-description');

            if (countrySelect.value) {
                confirmBtn.disabled = false;

                const option = countrySelect.selectedOptions[0];
                const hasLocalData = option.dataset.hasLocalData === '1';
                const hasApi = option.dataset.hasApi === '1';

                let infoType = hasLocalData ? 'local_data' : 'api_data';
                const info = this.dataSourceDescriptions[infoType];

                if (titleEl && descEl) {
                    titleEl.textContent = info.title;
                    descEl.textContent = info.description;
                }

                if (infoDiv) {
                    infoDiv.style.display = 'block';
                    infoDiv.className = 'data-source-info ' + (hasLocalData ? 'local-data' : 'api-data');
                }
            } else {
                confirmBtn.disabled = true;
                if (infoDiv) {
                    infoDiv.style.display = 'none';
                }
            }
        },

        // Confirm country selection
        confirmCountrySelection: function() {
            const countrySelect = document.getElementById('country-select');
            if (!countrySelect || !countrySelect.value) {
                this.showNotification('Please select a country first.', 'warning');
                return;
            }

            const countryCode = countrySelect.value;
            this.showLoadingState('Setting up your service country...');

            const formData = new FormData();
            formData.append('action', 'mobooking_set_service_country');
            formData.append('country_code', countryCode);
            formData.append('nonce', mobooking_area_vars.nonce || '');

            fetch(mobooking_area_vars.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showNotification(data.data.message || 'Country updated successfully!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    this.showNotification(data.data || 'Error setting country', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showNotification('Network error occurred', 'error');
            })
            .finally(() => {
                this.hideLoadingState();
            });
        },

        // Handle city input
        handleCityInput: function(e) {
            // Enable search button when user types
            const searchBtn = document.getElementById('search-city-btn');
            if (searchBtn) {
                searchBtn.disabled = !e.target.value.trim();
            }

            // Future: Implement city suggestions/autocomplete here
        },

        // Handle city input keypress
        handleCityKeypress: function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.searchCityAreas();
            }
        },

        // Search for city areas
        searchCityAreas: function() {
            const cityInput = document.getElementById('city-search');
            if (!cityInput) return;

            const cityName = cityInput.value.trim();
            if (!cityName) {
                this.showNotification('Please enter a city name', 'warning');
                cityInput.focus();
                return;
            }

            // Get current country from various possible sources
            const countryCode = this.getCurrentCountry();
            if (!countryCode) {
                this.showNotification('Country not set. Please refresh the page.', 'error');
                return;
            }

            this.showSearchStatus();

            const formData = new FormData();
            formData.append('action', 'mobooking_fetch_city_areas');
            formData.append('city_name', cityName);
            formData.append('country_code', countryCode);
            formData.append('nonce', mobooking_area_vars.nonce || '');

            fetch(mobooking_area_vars.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.displayAreaResults(data.data);
                } else {
                    this.showNotification(data.data || 'Error fetching areas', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showNotification('Network error occurred', 'error');
            })
            .finally(() => {
                this.hideSearchStatus();
            });
        },

        // Get current country code
        getCurrentCountry: function() {
            // Try to get from various sources
            // const countryMeta = document.querySelector('meta[name="mobooking-country"]'); // This is fine if used
            // if (countryMeta) return countryMeta.content;

            // const countryData = document.querySelector('[data-country-code]'); // This is fine if used
            // if (countryData) return countryData.dataset.countryCode;

            // Extract from PHP variables if available (this is the primary one now)
            if (typeof window.mobooking_area_vars !== 'undefined' && typeof window.mobooking_area_vars.current_country !== 'undefined') {
                return window.mobooking_area_vars.current_country;
            }

            return null;
        },

        // Display area search results
        displayAreaResults: function(data) {
            const resultsDiv = document.getElementById('areas-results');
            const gridDiv = document.getElementById('areas-grid');
            const subtitle = resultsDiv?.querySelector('.results-subtitle');

            if (!resultsDiv || !gridDiv) {
                this.showNotification('Results container not found', 'error');
                return;
            }

            // Update subtitle with data source info
            const dataSourceText = data.is_nordic ? 'From local database' : 'From external APIs';
            if (subtitle) {
                subtitle.textContent = `${data.total_found || 0} areas found for ${data.city || 'city'} (${dataSourceText})`;
            }

            // Clear previous results
            gridDiv.innerHTML = '';
            this.selectedAreas.clear();

            if (data.areas && data.areas.length > 0) {
                data.areas.forEach(area => {
                    const areaItem = this.createAreaItem(area);
                    gridDiv.appendChild(areaItem);
                });

                resultsDiv.style.display = 'block';
                resultsDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                this.showNotification('No areas found for this city', 'info');
            }

            this.updateSelectionCount();
        },

        // Create area item element
        createAreaItem: function(area) {
            const item = document.createElement('div');
            item.className = 'area-item';

            const areaData = JSON.stringify(area).replace(/"/g, '&quot;');

            item.innerHTML = `
                <input type="checkbox" class="area-checkbox" data-area="${areaData}">
                <div class="area-content">
                    <div class="area-name">${this.escapeHtml(area.area_name || '')}</div>
                    <div class="area-details">
                        <span class="zip-code">${this.escapeHtml(area.zip_code || '')}</span>
                        ${area.state ? `<span class="area-state">${this.escapeHtml(area.state)}</span>` : ''}
                        <span class="data-source">${this.escapeHtml(area.source || 'Unknown')}</span>
                    </div>
                    ${area.latitude && area.longitude ? `
                        <div class="area-coordinates">
                            <small>📍 ${parseFloat(area.latitude).toFixed(4)}, ${parseFloat(area.longitude).toFixed(4)}</small>
                        </div>
                    ` : ''}
                </div>
            `;

            return item;
        },

        // Handle area checkbox change
        handleAreaCheckboxChange: function(checkbox) {
            const areaData = checkbox.dataset.area;
            if (checkbox.checked) {
                this.selectedAreas.add(areaData);
            } else {
                this.selectedAreas.delete(areaData);
            }
            this.updateSelectionCount();
        },

        // Toggle select all areas
        toggleSelectAll: function() {
            const checkboxes = document.querySelectorAll('#areas-grid .area-checkbox');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            const newState = !allChecked;

            checkboxes.forEach(cb => {
                cb.checked = newState;
                const areaData = cb.dataset.area;
                if (newState) {
                    this.selectedAreas.add(areaData);
                } else {
                    this.selectedAreas.delete(areaData);
                }
            });

            this.updateSelectionCount();
        },

        // Update selection count display
        updateSelectionCount: function() {
            const countEl = document.getElementById('selected-count');
            const saveBtn = document.getElementById('save-selected-btn');
            const selectAllBtn = document.getElementById('select-all-btn');

            const count = this.selectedAreas.size;

            if (countEl) {
                countEl.textContent = count;
            }

            if (saveBtn) {
                saveBtn.disabled = count === 0;
            }

            if (selectAllBtn) {
                const totalCheckboxes = document.querySelectorAll('#areas-grid .area-checkbox').length;
                const allSelected = count === totalCheckboxes && totalCheckboxes > 0;
                selectAllBtn.textContent = allSelected ? 'Deselect All' : 'Select All';
            }
        },

        // Save selected areas
        saveSelectedAreas: function() {
            if (this.selectedAreas.size === 0) {
                this.showNotification('Please select at least one area', 'warning');
                return;
            }

            if (this.isLoading) return;
            this.isLoading = true;

            const selectedAreasArray = Array.from(this.selectedAreas).map(areaDataStr => {
                try {
                    return JSON.parse(areaDataStr.replace(/&quot;/g, '"'));
                } catch (e) {
                    console.error('Error parsing area data:', e);
                    return null;
                }
            }).filter(area => area !== null);

            if (selectedAreasArray.length === 0) {
                this.showNotification('Error processing selected areas', 'error');
                this.isLoading = false;
                return;
            }

            this.showLoadingState('Saving selected areas...');

            const formData = new FormData();
            formData.append('action', 'mobooking_save_selected_areas');
            formData.append('selected_areas', JSON.stringify(selectedAreasArray));
            formData.append('nonce', mobooking_area_vars.nonce || '');

            fetch(mobooking_area_vars.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showNotification(data.data.message || 'Areas saved successfully!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    this.showNotification(data.data || 'Error saving areas', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showNotification('Network error occurred', 'error');
            })
            .finally(() => {
                this.hideLoadingState();
                this.isLoading = false;
            });
        },

        // Reset country selection
        resetCountrySelection: function() {
            if (!confirm('Are you sure? This will reset your country selection and remove all current areas.')) {
                return;
            }

            this.showLoadingState('Resetting country selection...');

            const formData = new FormData();
            formData.append('action', 'mobooking_reset_service_country');
            formData.append('nonce', mobooking_area_vars.nonce || '');

            fetch(mobooking_area_vars.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showNotification(data.data.message || 'Country reset successfully!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    this.showNotification(data.data || 'Error resetting country', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showNotification('Network error occurred', 'error');
            })
            .finally(() => {
                this.hideLoadingState();
            });
        },

        // Show add more areas interface
        showAddMoreAreas: function() {
            // Hide management view and show city search
            const managementDiv = document.querySelector('.areas-management');
            const cityDiv = document.querySelector('.first-city-container');

            if (managementDiv) managementDiv.style.display = 'none';
            if (cityDiv) {
                cityDiv.style.display = 'block';
                const cityInput = document.getElementById('city-search');
                if (cityInput) {
                    cityInput.value = '';
                    cityInput.focus();
                }
            }
        },

        // Area Management Events
        bindAreaManagementEvents: function() {
            // These are handled in handleDocumentClick and handleDocumentChange
            // This method is for any additional specific management events

            // Update bulk action state on page load
            this.updateBulkActionState();
        },

        // Handle status toggle
        handleStatusToggle: function(e) {
            const areaId = e.target.dataset.areaId;
            if (!areaId) return;

            const formData = new FormData();
            formData.append('action', 'mobooking_toggle_area_status');
            formData.append('area_id', areaId);
            formData.append('nonce', mobooking_area_vars.nonce || '');

            fetch(mobooking_area_vars.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showNotification(data.data.message || 'Status updated successfully!', 'success');

                    // Update the toggle label
                    const label = e.target.parentNode.querySelector('.toggle-label');
                    if (label) {
                        label.textContent = data.data.new_status ? 'Active' : 'Inactive';
                    }
                } else {
                    // Revert the toggle
                    e.target.checked = !e.target.checked;
                    this.showNotification(data.data || 'Error updating status', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                e.target.checked = !e.target.checked;
                this.showNotification('Network error occurred', 'error');
            });
        },

        // Handle delete area
        handleDeleteArea: function(e) {
            const deleteBtn = e.target.closest('.delete-area-btn');
            const areaId = deleteBtn?.dataset.areaId;
            const areaRow = deleteBtn?.closest('.area-row');
            const areaNameEl = areaRow?.querySelector('.area-name strong');

            if (!areaId || !areaRow || !areaNameEl) {
                this.showNotification('Error: Could not identify area to delete', 'error');
                return;
            }

            const areaName = areaNameEl.textContent;

            if (!confirm(`Are you sure you want to delete "${areaName}"?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'mobooking_delete_area');
            formData.append('area_id', areaId);
            formData.append('nonce', mobooking_area_vars.nonce || '');

            fetch(mobooking_area_vars.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showNotification(data.data.message || 'Area deleted successfully!', 'success');
                    areaRow.remove();
                    this.updateBulkActionState();
                } else {
                    this.showNotification(data.data || 'Error deleting area', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showNotification('Network error occurred', 'error');
            });
        },

        // Handle edit area
        handleEditArea: function(e) {
            const editBtn = e.target.closest('.edit-area-btn');
            const areaId = editBtn?.dataset.areaId;

            if (!areaId) {
                this.showNotification('Error: Could not identify area to edit', 'error');
                return;
            }

            // For now, just show a message. You can implement edit functionality later
            this.showNotification('Edit functionality coming soon!', 'info');
        },

        // Handle select all areas in management table
        handleSelectAllAreas: function(checked) {
            const checkboxes = document.querySelectorAll('.area-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = checked;
            });
            this.updateBulkActionState();
        },

        // Get selected area IDs
        getSelectedAreaIds: function() {
            const checkedBoxes = document.querySelectorAll('.area-checkbox:checked');
            return Array.from(checkedBoxes).map(cb => cb.value).filter(id => id);
        },

        // Update bulk action state
        updateBulkActionState: function() {
            const selectedIds = this.getSelectedAreaIds();
            const bulkSelect = document.getElementById('bulk-action-select');
            const applyBtn = document.getElementById('apply-bulk-action-btn');

            if (applyBtn) {
                applyBtn.disabled = !selectedIds.length || !bulkSelect?.value;
            }

            // Update select all checkbox
            const selectAllCheckbox = document.getElementById('select-all-areas');
            const allCheckboxes = document.querySelectorAll('.area-checkbox');

            if (selectAllCheckbox && allCheckboxes.length > 0) {
                const checkedCount = selectedIds.length;
                selectAllCheckbox.checked = checkedCount === allCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < allCheckboxes.length;
            }
        },

        // Handle bulk action
        handleBulkAction: function() {
            const selectedIds = this.getSelectedAreaIds();
            const bulkSelect = document.getElementById('bulk-action-select');
            const action = bulkSelect?.value;

            if (!selectedIds.length || !action) {
                this.showNotification('Please select areas and an action', 'warning');
                return;
            }

            const actionName = bulkSelect.selectedOptions[0].textContent;
            if (!confirm(`Are you sure you want to ${actionName.toLowerCase()} ${selectedIds.length} areas?`)) {
                return;
            }

            this.showLoadingState('Applying bulk action...');

            const formData = new FormData();
            formData.append('action', 'mobooking_bulk_area_action');
            formData.append('area_ids', JSON.stringify(selectedIds));
            formData.append('bulk_action', action);
            formData.append('nonce', mobooking_area_vars.nonce || '');

            fetch(mobooking_area_vars.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showNotification(data.data.message || 'Bulk action completed successfully!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    this.showNotification(data.data || 'Error applying bulk action', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showNotification('Network error occurred', 'error');
            })
            .finally(() => {
                this.hideLoadingState();
            });
        },

        // Show search status
        showSearchStatus: function() {
            const statusDiv = document.getElementById('search-status');
            if (statusDiv) {
                statusDiv.style.display = 'block';
            }
        },

        // Hide search status
        hideSearchStatus: function() {
            const statusDiv = document.getElementById('search-status');
            if (statusDiv) {
                statusDiv.style.display = 'none';
            }
        },

        // Show loading state
        showLoadingState: function(message) {
            let overlay = document.getElementById('loading-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'loading-overlay';
                overlay.className = 'loading-overlay';
                document.body.appendChild(overlay);
            }

            overlay.innerHTML = `
                <div class="loading-content">
                    <div class="loading-spinner"></div>
                    <div class="loading-message">${this.escapeHtml(message)}</div>
                </div>
            `;
            overlay.style.display = 'flex';
        },

        // Hide loading state
        hideLoadingState: function() {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.style.display = 'none';
            }
        },

        // Show notification
        showNotification: function(message, type = 'info') {
            // Remove existing notifications of the same type
            const existingNotifications = document.querySelectorAll(`.notification-${type}`);
            existingNotifications.forEach(n => n.remove());

            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <span class="notification-message">${this.escapeHtml(message)}</span>
                    <button class="notification-close" type="button">&times;</button>
                </div>
            `;

            // Add to page
            document.body.appendChild(notification);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);

            // Animate in
            requestAnimationFrame(() => {
                notification.classList.add('notification-show');
            });
        },

        // Escape HTML
        escapeHtml: function(text) {
            if (typeof text !== 'string') {
                return '';
            }
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Initialize the areas manager
    try {
        EnhancedAreasManager.init();
    } catch (error) {
        console.error('Error initializing Enhanced Areas Manager:', error);
    }

    // Make it globally available for debugging
    window.EnhancedAreasManager = EnhancedAreasManager;
});
</script>
<style>
/* Enhanced Areas Styles with Local JSON Support */
.areas-section {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.areas-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.areas-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.areas-title-group h1 {
    margin: 0 0 5px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.title-icon {
    width: 28px;
    height: 28px;
}

.areas-subtitle {
    margin: 0;
    opacity: 0.9;
    font-size: 16px;
}

.areas-stats {
    display: flex;
    gap: 30px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 28px;
    font-weight: bold;
    line-height: 1;
}

.stat-label {
    font-size: 14px;
    opacity: 0.8;
}

/* Country Selection */
.country-selection-card, .first-city-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    padding: 40px;
    text-align: center;
    max-width: 600px;
    margin: 0 auto;
}

.selection-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.selection-icon svg {
    width: 30px;
    height: 30px;
    color: white;
}

.selection-header h2 {
    margin: 0 0 15px 0;
    color: #333;
}

.selection-header p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 30px;
}

/* Data Quality Badges */
.data-quality-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
    margin-top: 15px;
}

.data-quality-badge.local-data {
    background: #e8f5e8;
    color: #2d5a2d;
    border: 1px solid #a5d6a5;
}

.data-quality-badge.api-data {
    background: #e6f3ff;
    color: #1f4d73;
    border: 1px solid #99c9ff;
}

.badge-icon {
    font-size: 16px;
}

/* Data Source Info */
.data-source-info {
    margin-top: 20px;
    text-align: left;
}

.data-source-info .info-card {
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid;
}

.data-source-info.local-data .info-card {
    background: #f0f9f0;
    border-left-color: #4caf50;
}

.data-source-info.api-data .info-card {
    background: #f0f8ff;
    border-left-color: #2196f3;
}

.info-card h4 {
    margin: 0 0 10px 0;
    color: #333;
}

.info-card p {
    margin: 0;
    color: #666;
    line-height: 1.5;
}

/* Form Elements */
.form-group {
    margin-bottom: 25px;
    text-align: left;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.country-dropdown, .city-search-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.country-dropdown:focus, .city-search-input:focus {
    outline: none;
    border-color: #667eea;
}

.field-help {
    margin-top: 8px;
    font-size: 14px;
    color: #666;
}

/* Search Input Group */
.search-input-group {
    position: relative;
}

.search-btn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 8px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.search-btn:hover {
    background: #5a6fd8;
}

.search-btn svg {
    width: 16px;
    height: 16px;
}

/* Buttons */
.btn-primary, .btn-secondary, .btn-outline {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 16px;
    border: 2px solid transparent;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary {
    background: #f8f9fa;
    color: #495057;
    border-color: #dee2e6;
}

.btn-secondary:hover {
    background: #e9ecef;
}

.btn-outline {
    background: transparent;
    color: #667eea;
    border-color: #667eea;
}

.btn-outline:hover {
    background: #667eea;
    color: white;
}

/* Areas Results */
.areas-results {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    padding: 30px;
    margin-top: 30px;
}

.results-header {
    text-align: center;
    margin-bottom: 30px;
}

.results-header h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.results-subtitle {
    color: #666;
    margin: 0;
}

/* Areas Grid */
.areas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.area-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 20px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    transition: all 0.3s;
    cursor: pointer;
}

.area-item:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.area-item input[type="checkbox"] {
    margin-top: 2px;
}

.area-content {
    flex: 1;
}

.area-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.area-details {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 8px;
}

.zip-code {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
}

.area-state {
    background: #f3e5f5;
    color: #7b1fa2;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 14px;
}

.data-source {
    background: #e8f5e8;
    color: #388e3c;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.area-coordinates {
    font-size: 12px;
    color: #666;
}

/* Selection Actions */
.selection-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
}

.selection-info {
    font-weight: 500;
    color: #333;
}

.action-buttons {
    display: flex;
    gap: 12px;
}

/* Areas Management */
.management-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}

.header-content h2 {
    margin: 0 0 5px 0;
    color: #333;
}

.data-source-indicator .indicator {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 16px;
    font-size: 14px;
    font-weight: 500;
}

.indicator.local-data {
    background: #e8f5e8;
    color: #2d5a2d;
}

.indicator.api-data {
    background: #e6f3ff;
    color: #1f4d73;
}

.header-actions {
    display: flex;
    gap: 12px;
}

/* Areas Table */
.areas-table-container {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.areas-table {
    width: 100%;
    border-collapse: collapse;
}

.areas-table th,
.areas-table td {
    padding: 16px;
    text-align: left;
    border-bottom: 1px solid #e1e5e9;
}

.areas-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #495057;
}

.areas-table tbody tr:hover {
    background: #f8f9fa;
}

/* Status Toggle */
.status-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.toggle-slider {
    position: relative;
    width: 40px;
    height: 20px;
    background: #ccc;
    border-radius: 20px;
    transition: background-color 0.3s;
}

.toggle-slider::before {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    background: white;
    border-radius: 50%;
    top: 2px;
    left: 2px;
    transition: transform 0.3s;
}

.status-toggle input:checked + .toggle-slider {
    background: #4caf50;
}

.status-toggle input:checked + .toggle-slider::before {
    transform: translateX(20px);
}

.status-toggle input {
    display: none;
}

/* Action Buttons */
.btn-icon {
    background: none;
    border: none;
    padding: 8px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn-icon:hover {
    background: #f8f9fa;
}

.btn-icon svg {
    width: 16px;
    height: 16px;
    color: #666;
}

.delete-area-btn:hover svg {
    color: #dc3545;
}

/* Bulk Actions */
.bulk-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
}

.bulk-actions-left {
    display: flex;
    gap: 12px;
    align-items: center;
}

.areas-count {
    color: #666;
    font-size: 14px;
}

/* Search Status */
.search-status {
    padding: 20px;
    text-align: center;
    background: #f8f9fa;
    border-radius: 8px;
    margin-top: 20px;
}

.status-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.status-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid #e1e5e9;
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Loading Overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-content {
    background: white;
    padding: 40px;
    border-radius: 12px;
    text-align: center;
    max-width: 300px;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e1e5e9;
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

.loading-message {
    color: #333;
    font-weight: 500;
}

/* Notifications */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    z-index: 10000;
    transform: translateX(400px);
    transition: transform 0.3s;
    min-width: 300px;
    max-width: 500px;
}

.notification-show {
    transform: translateX(0);
}

.notification-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
}

.notification-success {
    border-left: 4px solid #4caf50;
}

.notification-error {
    border-left: 4px solid #f44336;
}

.notification-warning {
    border-left: 4px solid #ff9800;
}

.notification-info {
    border-left: 4px solid #2196f3;
}

.notification-close {
    background: none;
    border: none;
    font-size: 18px;
    color: #666;
    cursor: pointer;
    padding: 0;
    margin-left: 12px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .areas-header-content {
        flex-direction: column;
        text-align: center;
    }

    .areas-stats {
        justify-content: center;
    }

    .management-header {
        flex-direction: column;
        align-items: stretch;
    }

    .header-actions {
        justify-content: center;
    }

    .areas-grid {
        grid-template-columns: 1fr;
    }

    .selection-actions {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
    }

    .bulk-actions {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
    }

    .areas-table-container {
        overflow-x: auto;
    }

    .notification {
        right: 10px;
        left: 10px;
        max-width: none;
        transform: translateY(-100px);
    }

    .notification-show {
        transform: translateY(0);
    }
}
</style>
