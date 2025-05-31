<?php
// dashboard/sections/areas.php - Enhanced Service Areas Management with City Selection Flow
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

// Countries supported by comprehensive city lists
$supported_countries = array(
    'US' => array(
        'name' => 'United States',
        'cities' => array(
            'New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose',
            'Austin', 'Jacksonville', 'Fort Worth', 'Columbus', 'Charlotte', 'San Francisco', 'Indianapolis', 'Seattle', 'Denver', 'Washington',
            'Boston', 'El Paso', 'Nashville', 'Detroit', 'Oklahoma City', 'Portland', 'Las Vegas', 'Memphis', 'Louisville', 'Baltimore',
            'Milwaukee', 'Albuquerque', 'Tucson', 'Fresno', 'Sacramento', 'Kansas City', 'Mesa', 'Atlanta', 'Omaha', 'Colorado Springs',
            'Raleigh', 'Miami', 'Virginia Beach', 'Oakland', 'Minneapolis', 'Tulsa', 'Arlington', 'Tampa', 'New Orleans', 'Wichita'
        )
    ),
    'CA' => array(
        'name' => 'Canada',
        'cities' => array(
            'Toronto', 'Montreal', 'Calgary', 'Ottawa', 'Edmonton', 'Mississauga', 'Winnipeg', 'Vancouver', 'Brampton', 'Hamilton',
            'Quebec City', 'Surrey', 'Laval', 'Halifax', 'London', 'Markham', 'Vaughan', 'Gatineau', 'Saskatoon', 'Longueuil',
            'Burnaby', 'Regina', 'Richmond', 'Richmond Hill', 'Oakville', 'Burlington', 'Sherbrooke', 'Oshawa', 'Saguenay', 'Levis'
        )
    ),
    'GB' => array(
        'name' => 'United Kingdom',
        'cities' => array(
            'London', 'Birmingham', 'Manchester', 'Glasgow', 'Liverpool', 'Leeds', 'Sheffield', 'Edinburgh', 'Bristol', 'Cardiff',
            'Belfast', 'Leicester', 'Coventry', 'Bradford', 'Nottingham', 'Hull', 'Newcastle', 'Stoke-on-Trent', 'Southampton', 'Derby',
            'Portsmouth', 'Brighton', 'Plymouth', 'Northampton', 'Reading', 'Luton', 'Wolverhampton', 'Bolton', 'Bournemouth', 'Norwich'
        )
    ),
    'DE' => array(
        'name' => 'Germany',
        'cities' => array(
            'Berlin', 'Hamburg', 'Munich', 'Cologne', 'Frankfurt', 'Stuttgart', 'Düsseldorf', 'Dortmund', 'Essen', 'Leipzig',
            'Bremen', 'Dresden', 'Hanover', 'Nuremberg', 'Duisburg', 'Bochum', 'Wuppertal', 'Bielefeld', 'Bonn', 'Münster',
            'Karlsruhe', 'Mannheim', 'Augsburg', 'Wiesbaden', 'Gelsenkirchen', 'Mönchengladbach', 'Braunschweig', 'Chemnitz', 'Kiel', 'Aachen'
        )
    ),
    'FR' => array(
        'name' => 'France',
        'cities' => array(
            'Paris', 'Marseille', 'Lyon', 'Toulouse', 'Nice', 'Nantes', 'Strasbourg', 'Montpellier', 'Bordeaux', 'Lille',
            'Rennes', 'Reims', 'Le Havre', 'Saint-Étienne', 'Toulon', 'Angers', 'Grenoble', 'Dijon', 'Nîmes', 'Aix-en-Provence'
        )
    ),
    'ES' => array(
        'name' => 'Spain',
        'cities' => array(
            'Madrid', 'Barcelona', 'Valencia', 'Seville', 'Zaragoza', 'Málaga', 'Murcia', 'Palma', 'Las Palmas', 'Bilbao',
            'Alicante', 'Córdoba', 'Valladolid', 'Vigo', 'Gijón', 'Hospitalet', 'La Coruña', 'Vitoria-Gasteiz', 'Granada', 'Elche'
        )
    ),
    'IT' => array(
        'name' => 'Italy',
        'cities' => array(
            'Rome', 'Milan', 'Naples', 'Turin', 'Palermo', 'Genoa', 'Bologna', 'Florence', 'Bari', 'Catania',
            'Venice', 'Verona', 'Messina', 'Padua', 'Trieste', 'Taranto', 'Brescia', 'Parma', 'Prato', 'Modena'
        )
    ),
    'AU' => array(
        'name' => 'Australia',
        'cities' => array(
            'Sydney', 'Melbourne', 'Brisbane', 'Perth', 'Adelaide', 'Gold Coast', 'Canberra', 'Newcastle', 'Wollongong', 'Logan City',
            'Geelong', 'Hobart', 'Townsville', 'Cairns', 'Darwin', 'Toowoomba', 'Ballarat', 'Bendigo', 'Albury', 'Launceston'
        )
    ),
    'CY' => array(
        'name' => 'Cyprus',
        'cities' => array(
            'Nicosia', 'Limassol', 'Larnaca', 'Paphos', 'Famagusta', 'Kyrenia', 'Protaras', 'Ayia Napa', 'Polis', 'Paralimni',
            'Deryneia', 'Strovolos', 'Lakatamia', 'Aglantzia', 'Engomi', 'Perivolia', 'Livadia', 'Aradippou', 'Kiti', 'Oroklini'
        )
    ),
    'SE' => array(
        'name' => 'Sweden',
        'cities' => array(
            'Stockholm', 'Gothenburg', 'Malmö', 'Uppsala', 'Västerås', 'Örebro', 'Linköping', 'Helsingborg', 'Jönköping', 'Norrköping',
            'Lund', 'Umeå', 'Gävle', 'Borås', 'Eskilstuna', 'Södertälje', 'Karlstad', 'Täby', 'Växjö', 'Halmstad',
            'Sundsvall', 'Luleå', 'Trollhättan', 'Östersund', 'Borlänge', 'Tumba', 'Kiruna', 'Kalmar', 'Kristianstad', 'Skövde'
        )
    )
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
                        <span class="stat-number"><?php echo esc_html($supported_countries[$selected_country]['name'] ?? $selected_country); ?></span>
                        <span class="stat-label"><?php _e('Country', 'mobooking'); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="areas-header-actions">
            <?php if ($selected_country && !empty($areas)) : ?>
                <button type="button" id="manage-cities-btn" class="btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <?php _e('Manage Cities', 'mobooking'); ?>
                </button>
                <button type="button" id="change-country-btn" class="btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                    </svg>
                    <?php _e('Change Country', 'mobooking'); ?>
                </button>
            <?php elseif ($selected_country) : ?>
                <button type="button" id="select-cities-btn" class="btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <?php _e('Select Cities', 'mobooking'); ?>
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
                    <p><?php _e('Choose the country where you provide services. You can only select one country per business.', 'mobooking'); ?></p>
                </div>
                
                <div class="country-selection-form">
                    <div class="form-group">
                        <label for="country-select"><?php _e('Country', 'mobooking'); ?></label>
                        <select id="country-select" class="country-dropdown">
                            <option value=""><?php _e('Select a country...', 'mobooking'); ?></option>
                            <?php foreach ($supported_countries as $code => $info) : ?>
                                <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($info['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="field-help"><?php _e('This will determine which cities are available for your service areas.', 'mobooking'); ?></p>
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
        <!-- Step 2: City Selection -->
        <div class="city-selection-container">
            <div class="city-selection-card">
                <div class="selection-header">
                    <div class="selection-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                    <h2><?php printf(__('Select Cities in %s', 'mobooking'), $supported_countries[$selected_country]['name']); ?></h2>
                    <p><?php _e('Choose the cities where you provide services. We\'ll automatically fetch ZIP codes and area names for each selected city.', 'mobooking'); ?></p>
                </div>
                
                <div class="city-selection-content">
                    <div class="city-selection-toolbar">
                        <div class="city-search">
                            <input type="text" id="city-search-input" placeholder="<?php _e('Search cities...', 'mobooking'); ?>" class="search-input">
                            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.35-4.35"/>
                            </svg>
                        </div>
                        <div class="city-selection-actions">
                            <button type="button" id="select-all-cities-btn" class="btn-secondary btn-sm">
                                <?php _e('Select All', 'mobooking'); ?>
                            </button>
                            <button type="button" id="clear-all-cities-btn" class="btn-secondary btn-sm">
                                <?php _e('Clear All', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="cities-grid" id="cities-grid">
                        <?php foreach ($supported_countries[$selected_country]['cities'] as $city) : ?>
                            <label class="city-item">
                                <input type="checkbox" class="city-checkbox" value="<?php echo esc_attr($city); ?>">
                                <div class="city-card">
                                    <div class="city-name"><?php echo esc_html($city); ?></div>
                                    <div class="city-status">
                                        <span class="status-indicator"></span>
                                        <span class="status-text"><?php _e('Available', 'mobooking'); ?></span>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="city-selection-summary">
                        <div class="selected-count">
                            <span id="selected-cities-count">0</span> <?php _e('cities selected', 'mobooking'); ?>
                        </div>
                        <button type="button" id="save-cities-btn" class="btn-primary btn-large" disabled>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                <polyline points="17,21 17,13 7,13 7,21"/>
                                <polyline points="7,3 7,8 15,8"/>
                            </svg>
                            <span class="btn-text"><?php _e('Save Selected Cities', 'mobooking'); ?></span>
                            <span class="btn-loading" style="display: none;"><?php _e('Processing...', 'mobooking'); ?></span>
                        </button>
                    </div>
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
                        <div class="area-row enhanced" data-area-id="<?php echo esc_attr($area->id); ?>" data-city="<?php echo esc_attr($area->city_name ?: $area->label); ?>">
                            <div class="grid-cell select">
                                <input type="checkbox" class="area-checkbox" value="<?php echo esc_attr($area->id); ?>">
                            </div>
                            <div class="grid-cell city">
                                <div class="city-info">
                                    <span class="city-name"><?php echo esc_html($area->city_name ?: $area->label ?: 'Unnamed Area'); ?></span>
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

<!-- Cities Processing Modal -->
<div id="cities-processing-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-content">
        <h3><?php _e('Processing Cities', 'mobooking'); ?></h3>
        <div class="processing-content">
            <div class="processing-spinner">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 12a9 9 0 11-6.219-8.56"/>
                </svg>
            </div>
            <p id="processing-message"><?php _e('Fetching ZIP codes for selected cities...', 'mobooking'); ?></p>
            <div class="processing-progress">
                <div class="progress-bar">
                    <div class="progress-fill" id="processing-progress-fill" style="width: 0%;"></div>
                </div>
                <div class="progress-text">
                    <span id="processing-current">0</span> / <span id="processing-total">0</span> cities processed
                </div>
            </div>
            <div class="processing-log">
                <div id="processing-log-content"></div>
            </div>
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
.selection-header { margin-bottom: 2rem;    padding: 2rem;
 }
.selection-icon { width: 64px; height: 64px; background: linear-gradient(135deg, var(--primary), #1d4ed8); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: white; }
.selection-icon svg { width: 32px; height: 32px; }
.country-selection-form .form-group { margin-bottom: 1.5rem; text-align: left; }
.country-dropdown { width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 8px; font-size: 1rem; transition: all 0.2s; }
.country-dropdown:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }

/* City Selection Styles */
.city-selection-container { display: flex; justify-content: center; align-items: flex-start; min-height: 60vh; padding: 2rem 0; }
.city-selection-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); max-width: 1000px; width: 100%; overflow: hidden; }
.city-selection-content { padding: 2rem; }
.city-selection-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
.city-search { position: relative; flex: 1; max-width: 400px; }
.city-search .search-input { width: 100%; padding: 0.75rem 2.5rem 0.75rem 1rem; border: 2px solid var(--gray-200); border-radius: 8px; font-size: 1rem; transition: all 0.2s; }
.city-search .search-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.city-search .search-icon { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; color: var(--gray-400); }
.city-selection-actions { display: flex; gap: 0.5rem; }
.cities-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 2rem; max-height: 500px; overflow-y: auto; padding: 0.5rem; }
.city-item { cursor: pointer; transition: all 0.2s; }
.city-item input[type="checkbox"] { position: absolute; opacity: 0; pointer-events: none; }
.city-card { padding: 1.5rem; border: 2px solid var(--gray-200); border-radius: 8px; background: white; transition: all 0.2s; display: flex; justify-content: space-between; align-items: center; }
.city-card:hover { border-color: var(--primary); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1); }
.city-item input:checked + .city-card { border-color: var(--primary); background: rgba(59, 130, 246, 0.05); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15); }
.city-name { font-size: 1.125rem; font-weight: 600; color: var(--gray-900); }
.city-status { display: flex; align-items: center; gap: 0.5rem; }
.status-indicator { width: 12px; height: 12px; border-radius: 50%; background: var(--success); }
.city-item input:checked + .city-card .status-indicator { background: var(--primary); }
.status-text { font-size: 0.875rem; color: var(--gray-600); }
.city-item input:checked + .city-card .status-text { color: var(--primary); font-weight: 500; }
.city-selection-summary { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; background: var(--gray-50); border-radius: 8px; border-top: 1px solid var(--gray-200); }
.selected-count { font-size: 1.125rem; font-weight: 600; color: var(--gray-700); }

/* Processing Modal Styles */
.processing-content { text-align: center; padding: 2rem; }
.processing-spinner { width: 64px; height: 64px; margin: 0 auto 1.5rem; color: var(--primary); }
.processing-spinner svg { width: 100%; height: 100%; animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.processing-progress { margin: 1.5rem 0; }
.progress-bar { width: 100%; height: 8px; background: var(--gray-200); border-radius: 4px; overflow: hidden; margin-bottom: 0.5rem; }
.progress-fill { height: 100%; background: linear-gradient(90deg, var(--primary), #1d4ed8); transition: width 0.3s ease; }
.progress-text { font-size: 0.875rem; color: var(--gray-600); }
.processing-log { margin-top: 1.5rem; max-height: 200px; overflow-y: auto; background: var(--gray-50); border-radius: 6px; padding: 1rem; text-align: left; }
.processing-log-content { font-family: 'Courier New', monospace; font-size: 0.875rem; color: var(--gray-700); }
.log-entry { margin-bottom: 0.5rem; padding: 0.25rem 0; }
.log-entry.success { color: var(--success); }
.log-entry.error { color: var(--danger); }
.log-entry.info { color: var(--primary); }

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
.areas-grid-header { display: grid; grid-template-columns: 40px 1fr 150px 100px 120px 140px; gap: 1rem; padding: 1rem; background: var(--gray-50); border-bottom: 1px solid var(--gray-200); font-weight: 600; color: var(--gray-700); }
.areas-grid-body { max-height: 600px; overflow-y: auto; }
.area-row { display: grid; grid-template-columns: 40px 1fr 150px 100px 120px 140px; gap: 1rem; padding: 1rem; border-bottom: 1px solid var(--gray-100); align-items: center; transition: all 0.2s; border-left: 4px solid transparent; }
.area-row:hover { background: var(--gray-50); border-left-color: var(--primary); }
.area-row:last-child { border-bottom: none; }

/* Grid Cells */
.grid-cell { display: flex; align-items: center; }
.select-checkbox, .area-checkbox { margin: 0; }
.city-info { display: flex; flex-direction: column; gap: 0.25rem; }
.city-name { font-weight: 600; color: var(--gray-900); }
.state-name { font-size: 0.875rem; color: var(--gray-500); }
.zip-codes-info { display: flex; flex-direction: column; gap: 0.25rem; }
.zip-count { font-weight: 500; color: var(--gray-700); }
.zip-preview { font-size: 0.875rem; color: var(--gray-500); font-family: 'Courier New', monospace; }
.zip-more { color: var(--primary); font-weight: 500; }
.no-zip-codes { color: var(--warning); font-style: italic; }

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

/* Modals */
.mobooking-modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; padding: 1rem; }
.modal-content { background: white; border-radius: 12px; max-width: 600px; width: 100%; margin: 2rem auto; position: relative; max-height: 90vh; overflow-y: auto; }
.modal-lg { max-width: 800px; }
.modal-close { position: absolute; top: 1rem; right: 1rem; width: 32px; height: 32px; border: none; background: var(--gray-100); border-radius: 50%; color: var(--gray-600); cursor: pointer; display: flex; align-items: center; justify-content: center; }
.modal-close:hover { background: var(--gray-200); }

/* Forms */
.form-group { margin-bottom: 1.5rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--gray-700); }
.form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 6px; font-size: 1rem; transition: all 0.2s; }
.form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.field-help { font-size: 0.875rem; color: var(--gray-500); margin-top: 0.25rem; }

/* ZIP Viewer */
.zip-codes-viewer { max-height: 70vh; display: flex; flex-direction: column; }
.zip-viewer-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--gray-200); }
.zip-search { position: relative; flex: 1; max-width: 300px; }
.zip-search input { width: 100%; padding: 0.5rem 2.5rem 0.5rem 1rem; border: 1px solid var(--gray-300); border-radius: 6px; font-size: 0.875rem; }
.zip-codes-display { flex: 1; overflow-y: auto; border: 1px solid var(--gray-200); border-radius: 6px; background: white; }
.zip-codes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.5rem; padding: 1rem; }
.zip-code-badge { display: flex; align-items: center; justify-content: center; padding: 0.5rem; background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 6px; font-family: 'Courier New', monospace; font-weight: 500; color: var(--gray-700); transition: all 0.2s; }
.zip-code-badge:hover { background: var(--primary); color: white; border-color: var(--primary); }

/* Confirmation Modal */
.confirmation-content { text-align: center; padding: 1rem 0; }
.warning-icon { width: 64px; height: 64px; background: var(--warning); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; }
.warning-icon svg { width: 32px; height: 32px; }

/* Form Actions */
.form-actions { display: flex; align-items: center; gap: 1rem; padding: 1.5rem; border-top: 1px solid var(--gray-200); }
.spacer { flex: 1; }

/* Buttons */
.btn-primary, .btn-secondary, .btn-danger { padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; border: none; }
.btn-primary { background: var(--primary); color: white; }
.btn-primary:hover:not(:disabled) { background: #2563eb; }
.btn-secondary { background: var(--gray-100); color: var(--gray-700); }
.btn-secondary:hover:not(:disabled) { background: var(--gray-200); }
.btn-danger { background: var(--danger); color: white; }
.btn-danger:hover:not(:disabled) { background: #dc2626; }
.btn-sm { padding: 0.5rem 1rem; font-size: 0.875rem; }
.btn-large { padding: 1rem 2rem; font-size: 1.125rem; }

/* Button Loading States */
.loading .btn-text { display: none; }
.loading .btn-loading { display: inline-flex; }
.btn-loading { display: none; }

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
    .zip-viewer-header { flex-direction: column; align-items: stretch; }
    .zip-codes-grid { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); }
    .country-selection-card { padding: 2rem 1.5rem; }
    .cities-grid { grid-template-columns: 1fr; }
    .city-selection-toolbar { flex-direction: column; align-items: stretch; }
    .city-search .search-input { max-width: none; }
    .city-selection-summary { flex-direction: column; gap: 1rem; text-align: center; }
}

@media (max-width: 480px) {
    .country-selection-card { padding: 1.5rem 1rem; }
    .zip-codes-grid { grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); }
    .form-actions { flex-direction: column-reverse; align-items: stretch; }
    .btn-primary, .btn-secondary, .btn-danger { width: 100%; justify-content: center; }
}

/* Animations */
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.area-row { animation: fadeIn 0.3s ease-out; }
.zip-code-badge { animation: fadeIn 0.2s ease-out; }
.city-card { animation: fadeIn 0.2s ease-out; }

/* Focus Styles */
.btn-primary:focus, .btn-secondary:focus, .btn-danger:focus { outline: 2px solid var(--primary); outline-offset: 2px; }
.form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
</style>

<script>
// Enhanced Areas Management JavaScript with City Selection Flow
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
            selectedCities: [],
            processedCities: 0,
            totalCities: 0,
            processingErrors: [],
            processedCitiesData: []
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
            
            // City selection
            $('#select-cities-btn, #manage-cities-btn').on('click', function() {
                self.showCitySelection();
            });
            
            // City search
            $('#city-search-input').on('input', function() {
                self.filterCities($(this).val());
            });
            
            // City selection actions
            $('#select-all-cities-btn').on('click', function() {
                $('.city-checkbox:visible').prop('checked', true);
                self.updateSelectedCount();
            });
            
            $('#clear-all-cities-btn').on('click', function() {
                $('.city-checkbox').prop('checked', false);
                self.updateSelectedCount();
            });
            
            // City checkboxes
            $(document).on('change', '.city-checkbox', function() {
                self.updateSelectedCount();
            });
            
            // Save cities
            $('#save-cities-btn').on('click', function() {
                self.saveSelectedCities();
            });
            
            // Area management
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
            
            // Export ZIP codes
            $('#export-zip-codes-btn').on('click', function() {
                self.exportZipCodes();
            });
        },
        
        initializeComponents: function() {
            this.updateSelectedCount();
            this.updateAreaCount();
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
        
        showCitySelection: function() {
            // Show the city selection interface (already visible in the template)
            // This could open a modal if needed or scroll to the city selection
            if ($('.city-selection-container').length) {
                $('html, body').animate({
                    scrollTop: $('.city-selection-container').offset().top - 100
                }, 500);
            }
        },
        
        filterCities: function(searchTerm) {
            const $cityItems = $('.city-item');
            
            if (!searchTerm) {
                $cityItems.show();
                return;
            }
            
            searchTerm = searchTerm.toLowerCase();
            
            $cityItems.each(function() {
                const cityName = $(this).find('.city-name').text().toLowerCase();
                const matches = cityName.includes(searchTerm);
                $(this).toggle(matches);
            });
        },
        
        updateSelectedCount: function() {
            const selectedCount = $('.city-checkbox:checked').length;
            $('#selected-cities-count').text(selectedCount);
            $('#save-cities-btn').prop('disabled', selectedCount === 0);
            
            if (selectedCount > 0) {
                $('#save-cities-btn').find('.btn-text').text(`Save ${selectedCount} Selected Cities`);
            } else {
                $('#save-cities-btn').find('.btn-text').text('Save Selected Cities');
            }
        },
        
        saveSelectedCities: function() {
            const selectedCities = [];
            $('.city-checkbox:checked').each(function() {
                selectedCities.push($(this).val());
            });
            
            if (selectedCities.length === 0) {
                this.showNotification('Please select at least one city', 'warning');
                return;
            }
            
            this.state.selectedCities = selectedCities;
            this.state.totalCities = selectedCities.length;
            this.state.processedCities = 0;
            this.state.processingErrors = [];
            this.state.processedCitiesData = [];
            
            // Show processing modal
            this.showProcessingModal();
            
            // Start processing cities
            this.processCities();
        },
        
        showProcessingModal: function() {
            $('#processing-total').text(this.state.totalCities);
            $('#processing-current').text(0);
            $('#processing-progress-fill').css('width', '0%');
            $('#processing-log-content').empty();
            this.showModal('#cities-processing-modal');
        },
        
        processCities: function() {
            const self = this;
            let processedCount = 0;
            const cities = this.state.selectedCities;
            const country = this.config.selectedCountry;
            
            // Process cities in smaller batches to be more reliable
            const batchSize = 3;
            let currentBatch = 0;
            
            function processBatch() {
                const start = currentBatch * batchSize;
                const end = Math.min(start + batchSize, cities.length);
                const batch = cities.slice(start, end);
                
                const promises = batch.map(city => self.generateMockCityData(city, country));
                
                Promise.allSettled(promises).then(results => {
                    results.forEach((result, index) => {
                        const city = batch[index];
                        processedCount++;
                        
                        if (result.status === 'fulfilled' && result.value.success) {
                            const cityData = result.value;
                            self.addProcessingLog(`✓ ${city}: Generated ${cityData.zipCodes.length} ZIP codes`, 'success');
                            
                            // Store processed city data
                            self.state.processedCitiesData.push({
                                city_name: cityData.cityName,
                                state: cityData.state,
                                zip_codes: cityData.zipCodes,
                                active: true,
                                description: `Service area for ${cityData.cityName}${cityData.state ? ', ' + cityData.state : ''}`
                            });
                        } else {
                            const error = result.status === 'rejected' ? result.reason : result.value.error;
                            self.addProcessingLog(`✗ ${city}: ${error}`, 'error');
                            self.state.processingErrors.push({city, error});
                        }
                        
                        // Update progress
                        const progress = (processedCount / cities.length) * 100;
                        $('#processing-current').text(processedCount);
                        $('#processing-progress-fill').css('width', progress + '%');
                    });
                    
                    currentBatch++;
                    
                    if (currentBatch * batchSize < cities.length) {
                        // Process next batch after a delay
                        setTimeout(processBatch, 1000);
                    } else {
                        // All cities processed
                        self.completeProcessing();
                    }
                });
            }
            
            // Start processing
            processBatch();
        },


generateSwedishPostcodes: function(cityName) {
    const cityHash = this.hashString(cityName);
    const baseCode = 10000 + (cityHash % 80000);
    const postcodes = [];
    
    for (let i = 0; i < 5; i++) {
        // Swedish postal codes are 5 digits with a space after 3 digits (XXX XX)
        const code = (baseCode + i * 10).toString().padStart(5, '0');
        const formatted = code.substring(0, 3) + ' ' + code.substring(3, 5);
        postcodes.push(formatted);
    }
    
    return postcodes;
},

generateCypriotPostcodes: function(cityName) {
    const cityHash = this.hashString(cityName);
    const baseCode = 1000 + (cityHash % 8000);
    const postcodes = [];
    
    for (let i = 0; i < 5; i++) {
        // Cyprus postal codes are 4 digits
        const code = (baseCode + i * 10).toString().padStart(4, '0');
        postcodes.push(code);
    }
    
    return postcodes;
},

// Update the existing generateMockCityData function:
generateMockCityData: function(cityName, countryCode) {
    return new Promise((resolve) => {
        const mockZipPatterns = {
            // 'US': () => this.generateUSZipCodes(cityName),
            // 'GB': () => this.generateUKPostcodes(cityName),
            'CA': () => this.generateCanadianPostalCodes(cityName),
            'DE': () => this.generateGermanPostcodes(cityName),
            'FR': () => this.generateFrenchPostcodes(cityName),
            'ES': () => this.generateSpanishPostcodes(cityName),
            'IT': () => this.generateItalianPostcodes(cityName),
            'AU': () => this.generateAustralianPostcodes(cityName),
            'SE': () => this.generateSwedishPostcodes(cityName),
            'CY': () => this.generateCypriotPostcodes(cityName)
        };
        
        const generator = mockZipPatterns[countryCode];
        if (generator) {
            const zipCodes = generator();
            resolve({
                success: true,
                cityName: cityName,
                state: '',
                zipCodes: zipCodes,
                source: 'Generated Data'
            });
        } else {
            resolve({
                success: false,
                error: `No pattern available for country: ${countryCode}`
            });
        }
    });
},
        
        generateUSZipCodes: function(cityName) {
            const cityHash = this.hashString(cityName);
            const baseCode = 10000 + (cityHash % 80000);
            const zipCodes = [];
            
            for (let i = 0; i < 5; i++) {
                const code = (baseCode + i * 100).toString().padStart(5, '0');
                zipCodes.push(code);
            }
            
            return zipCodes;
        },
        
        generateUKPostcodes: function(cityName) {
            const areas = ['SW', 'NW', 'SE', 'NE', 'W', 'E', 'N', 'S'];
            const cityHash = this.hashString(cityName);
            const area = areas[cityHash % areas.length];
            const postcodes = [];
            
            for (let i = 1; i <= 5; i++) {
                const district = (cityHash % 20) + i;
                const sector = String.fromCharCode(65 + (cityHash + i) % 26);
                const unit = String.fromCharCode(65 + (cityHash + i * 2) % 26);
                postcodes.push(`${area}${district} ${i}${sector}${unit}`);
            }
            
            return postcodes;
        },
        
        generateCanadianPostalCodes: function(cityName) {
            const provinces = ['K', 'M', 'V', 'T', 'H', 'S', 'R', 'E'];
            const cityHash = this.hashString(cityName);
            const province = provinces[cityHash % provinces.length];
            const postcodes = [];
            
            for (let i = 1; i <= 5; i++) {
                const district = (cityHash % 9) + i;
                const letter1 = String.fromCharCode(65 + (cityHash + i) % 26);
                const number1 = (cityHash + i) % 10;
                const letter2 = String.fromCharCode(65 + (cityHash + i * 2) % 26);
                const number2 = (cityHash + i * 2) % 10;
                postcodes.push(`${province}${district}${letter1} ${number1}${letter2}${number2}`);
            }
            
            return postcodes;
        },
        
        generateGermanPostcodes: function(cityName) {
            const cityHash = this.hashString(cityName);
            const baseCode = 10000 + (cityHash % 80000);
            const postcodes = [];
            
            for (let i = 0; i < 5; i++) {
                const code = (baseCode + i * 10).toString().padStart(5, '0');
                postcodes.push(code);
            }
            
            return postcodes;
        },
        
        generateFrenchPostcodes: function(cityName) {
            const cityHash = this.hashString(cityName);
            const baseCode = 10000 + (cityHash % 85000);
            const postcodes = [];
            
            for (let i = 0; i < 5; i++) {
                const code = (baseCode + i * 10).toString().padStart(5, '0');
                postcodes.push(code);
            }
            
            return postcodes;
        },
        
        generateSpanishPostcodes: function(cityName) {
            const cityHash = this.hashString(cityName);
            const baseCode = 10000 + (cityHash % 40000);
            const postcodes = [];
            
            for (let i = 0; i < 5; i++) {
                const code = (baseCode + i * 10).toString().padStart(5, '0');
                postcodes.push(code);
            }
            
            return postcodes;
        },
        
        generateItalianPostcodes: function(cityName) {
            const cityHash = this.hashString(cityName);
            const baseCode = 10000 + (cityHash % 80000);
            const postcodes = [];
            
            for (let i = 0; i < 5; i++) {
                const code = (baseCode + i * 10).toString().padStart(5, '0');
                postcodes.push(code);
            }
            
            return postcodes;
        },
        
        generateAustralianPostcodes: function(cityName) {
            const cityHash = this.hashString(cityName);
            const baseCode = 1000 + (cityHash % 8000);
            const postcodes = [];
            
            for (let i = 0; i < 5; i++) {
                const code = (baseCode + i * 10).toString().padStart(4, '0');
                postcodes.push(code);
            }
            
            return postcodes;
        },
        
        hashString: function(str) {
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash; // Convert to 32bit integer
            }
            return Math.abs(hash);
        },
        
        addProcessingLog: function(message, type = 'info') {
            const $logContent = $('#processing-log-content');
            const $entry = $(`<div class="log-entry ${type}">${message}</div>`);
            $logContent.append($entry);
            
            // Auto-scroll to bottom
            $logContent.scrollTop($logContent[0].scrollHeight);
        },
        
        completeProcessing: function() {
            const successCount = this.state.processedCitiesData.length;
            const errorCount = this.state.processingErrors.length;
            
            this.addProcessingLog(`\n=== Processing Complete ===`, 'info');
            this.addProcessingLog(`Successfully processed: ${successCount} cities`, 'success');
            
            if (errorCount > 0) {
                this.addProcessingLog(`Failed: ${errorCount} cities`, 'error');
            }
            
            // Save the successfully processed cities to database
            if (successCount > 0) {
                setTimeout(() => {
                    this.saveCitiesToDatabase();
                }, 1000);
            } else {
                this.addProcessingLog('No cities were successfully processed.', 'error');
                setTimeout(() => {
                    this.hideModals();
                }, 5000);
            }
        },
        
        saveCitiesToDatabase: function() {
            this.addProcessingLog('Saving cities to database...', 'info');
            
            const processedData = this.state.processedCitiesData;
            
            if (processedData.length === 0) {
                this.addProcessingLog('No valid city data to save', 'error');
                setTimeout(() => {
                    this.hideModals();
                }, 3000);
                return;
            }
            
            const data = {
                action: 'mobooking_save_processed_cities',
                cities_data: JSON.stringify(processedData),
                country: this.config.selectedCountry,
                nonce: this.config.nonce
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                timeout: 60000, // Increased timeout for large datasets
                success: (response) => {
                    if (response.success) {
                        this.addProcessingLog(`✓ Successfully saved ${response.data.saved_count} cities to database!`, 'success');
                        
                        if (response.data.errors && response.data.errors.length > 0) {
                            this.addProcessingLog('Some errors occurred:', 'warning');
                            response.data.errors.forEach(error => {
                                this.addProcessingLog(`  - ${error}`, 'error');
                            });
                        }
                        
                        setTimeout(() => {
                            this.hideModals();
                            location.reload();
                        }, 3000);
                    } else {
                        this.addProcessingLog('✗ Failed to save cities to database', 'error');
                        if (response.data && response.data.errors) {
                            response.data.errors.forEach(error => {
                                this.addProcessingLog(`  - ${error}`, 'error');
                            });
                        }
                        setTimeout(() => {
                            this.hideModals();
                        }, 5000);
                    }
                },
                error: (xhr, status, error) => {
                    this.addProcessingLog('✗ Error communicating with server: ' + error, 'error');
                    setTimeout(() => {
                        this.hideModals();
                    }, 5000);
                }
            });
        },
        
        // Existing methods for area management...
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
            
            $('#zip-view-modal-title').text(`ZIP Codes for ${area.city_name || area.label}`);
            
            const $display = $('#zip-codes-display');
            $display.empty();
            
            if (zipCodes.length === 0) {
                $display.html(`
                    <div class="no-zip-codes-message" style="text-align: center; padding: 2rem;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 48px; height: 48px; margin-bottom: 1rem; color: #9ca3af;">
                            <path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/>
                        </svg>
                        <p style="color: #6b7280;">No ZIP codes available for this area.</p>
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