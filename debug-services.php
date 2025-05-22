

<?php
/**
 * Advanced Debug Box for MoBooking Services
 * Place this at the bottom of dashboard/sections/services.php
 */

// Only show debug box if user is admin and WP_DEBUG is true
if (current_user_can('administrator') && defined('WP_DEBUG') && WP_DEBUG) :
?>

<style>
.mobooking-debug-box {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 400px;
    max-height: 80vh;
    background: #1e1e1e;
    color: #e0e0e0;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    font-family: 'Fira Code', 'Courier New', monospace;
    font-size: 12px;
    z-index: 99999;
    overflow: hidden;
    border: 2px solid #333;
}

.debug-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: move;
    user-select: none;
}

.debug-title {
    font-weight: bold;
    font-size: 14px;
    color: white;
}

.debug-toggle {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.debug-content {
    max-height: calc(80vh - 50px);
    overflow-y: auto;
    padding: 0;
}

.debug-section {
    border-bottom: 1px solid #333;
    margin: 0;
}

.debug-section-header {
    background: #2a2a2a;
    padding: 8px 16px;
    font-weight: bold;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: background 0.2s;
}

.debug-section-header:hover {
    background: #3a3a3a;
}

.debug-section-content {
    padding: 12px 16px;
    display: none;
    background: #1e1e1e;
}

.debug-section.active .debug-section-content {
    display: block;
}

.debug-section.active .debug-section-header {
    background: #3a3a3a;
}

.debug-table {
    width: 100%;
    border-collapse: collapse;
    margin: 8px 0;
    font-size: 11px;
}

.debug-table th,
.debug-table td {
    padding: 4px 8px;
    text-align: left;
    border-bottom: 1px solid #444;
}

.debug-table th {
    background: #333;
    color: #fff;
    font-weight: bold;
}

.debug-table td {
    word-break: break-all;
}

.debug-status {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: bold;
}

.status-success {
    background: #4CAF50;
    color: white;
}

.status-error {
    background: #f44336;
    color: white;
}

.status-warning {
    background: #FF9800;
    color: white;
}

.status-info {
    background: #2196F3;
    color: white;
}

.debug-code {
    background: #000;
    padding: 8px;
    border-radius: 4px;
    font-family: 'Fira Code', monospace;
    font-size: 10px;
    overflow-x: auto;
    margin: 4px 0;
    border-left: 3px solid #667eea;
}

.debug-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin: 8px 0;
}

.debug-metric {
    background: #2a2a2a;
    padding: 8px;
    border-radius: 4px;
    text-align: center;
}

.debug-metric-value {
    font-size: 16px;
    font-weight: bold;
    color: #667eea;
}

.debug-metric-label {
    font-size: 10px;
    color: #aaa;
    margin-top: 2px;
}

.debug-arrow {
    transition: transform 0.2s;
}

.debug-section.active .debug-arrow {
    transform: rotate(90deg);
}

.debug-scrollable {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #333;
    border-radius: 4px;
}

.debug-json {
    background: #000;
    padding: 8px;
    border-radius: 4px;
    white-space: pre-wrap;
    font-size: 10px;
    max-height: 150px;
    overflow-y: auto;
}

.debug-timestamp {
    color: #888;
    font-size: 10px;
    float: right;
}
</style>

<div class="mobooking-debug-box" id="mobooking-debug">
    <div class="debug-header">
        <div class="debug-title">🐛 MoBooking Debug Console</div>
        <button class="debug-toggle" onclick="toggleDebugContent()">📊</button>
    </div>
    
    <div class="debug-content" id="debug-content">
        <?php
        global $wpdb;
        
        // Get current timestamp
        $current_time = current_time('Y-m-d H:i:s');
        
        // Database connection info
        echo '<div class="debug-section active">';
        echo '<div class="debug-section-header" onclick="toggleSection(this)">';
        echo '<span>🔌 Database Connection</span>';
        echo '<span class="debug-arrow">▶</span>';
        echo '</div>';
        echo '<div class="debug-section-content">';
        
        // Test database connection
        $db_connected = $wpdb->check_connection();
        $db_version = $wpdb->get_var("SELECT VERSION()");
        
        echo '<div class="debug-grid">';
        echo '<div class="debug-metric">';
        echo '<div class="debug-metric-value">' . ($db_connected ? '✅' : '❌') . '</div>';
        echo '<div class="debug-metric-label">Connection</div>';
        echo '</div>';
        echo '<div class="debug-metric">';
        echo '<div class="debug-metric-value">' . esc_html($db_version) . '</div>';
        echo '<div class="debug-metric-label">MySQL Version</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="debug-code">';
        echo 'Host: ' . esc_html(DB_HOST) . '<br>';
        echo 'Database: ' . esc_html(DB_NAME) . '<br>';
        echo 'Charset: ' . esc_html(DB_CHARSET) . '<br>';
        echo 'Prefix: ' . esc_html($wpdb->prefix);
        echo '</div>';
        
        echo '<span class="debug-timestamp">Updated: ' . $current_time . '</span>';
        echo '</div>';
        echo '</div>';
        
        // Services Table Analysis
        echo '<div class="debug-section">';
        echo '<div class="debug-section-header" onclick="toggleSection(this)">';
        echo '<span>🏢 Services Table</span>';
        echo '<span class="debug-arrow">▶</span>';
        echo '</div>';
        echo '<div class="debug-section-content">';
        
        $services_table = $wpdb->prefix . 'mobooking_services';
        $services_exists = $wpdb->get_var("SHOW TABLES LIKE '$services_table'") == $services_table;
        
        if ($services_exists) {
            // Get table structure
            $services_columns = $wpdb->get_results("SHOW COLUMNS FROM $services_table");
            $services_count = $wpdb->get_var("SELECT COUNT(*) FROM $services_table");
            $user_services_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $services_table WHERE user_id = %d", get_current_user_id()));
            
            echo '<div class="debug-grid">';
            echo '<div class="debug-metric">';
            echo '<div class="debug-metric-value">' . $services_count . '</div>';
            echo '<div class="debug-metric-label">Total Services</div>';
            echo '</div>';
            echo '<div class="debug-metric">';
            echo '<div class="debug-metric-value">' . $user_services_count . '</div>';
            echo '<div class="debug-metric-label">Your Services</div>';
            echo '</div>';
            echo '</div>';
            
            // Table structure
            echo '<h4>📋 Table Structure:</h4>';
            echo '<div class="debug-scrollable">';
            echo '<table class="debug-table">';
            echo '<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead><tbody>';
            foreach ($services_columns as $column) {
                echo '<tr>';
                echo '<td>' . esc_html($column->Field) . '</td>';
                echo '<td>' . esc_html($column->Type) . '</td>';
                echo '<td>' . esc_html($column->Null) . '</td>';
                echo '<td>' . esc_html($column->Key) . '</td>';
                echo '<td>' . esc_html($column->Default) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
            
            // Recent services
            $recent_services = $wpdb->get_results($wpdb->prepare(
                "SELECT id, name, price, duration, status, created_at FROM $services_table WHERE user_id = %d ORDER BY created_at DESC LIMIT 5",
                get_current_user_id()
            ));
            
            if ($recent_services) {
                echo '<h4>📝 Recent Services:</h4>';
                echo '<div class="debug-scrollable">';
                echo '<table class="debug-table">';
                echo '<thead><tr><th>ID</th><th>Name</th><th>Price</th><th>Duration</th><th>Status</th><th>Created</th></tr></thead><tbody>';
                foreach ($recent_services as $service) {
                    echo '<tr>';
                    echo '<td>' . esc_html($service->id) . '</td>';
                    echo '<td>' . esc_html($service->name) . '</td>';
                    echo '<td>$' . esc_html($service->price) . '</td>';
                    echo '<td>' . esc_html($service->duration) . 'm</td>';
                    echo '<td><span class="debug-status status-' . ($service->status == 'active' ? 'success' : 'warning') . '">' . esc_html($service->status) . '</span></td>';
                    echo '<td>' . esc_html($service->created_at) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                echo '</div>';
            }
        } else {
            echo '<span class="debug-status status-error">❌ Table does not exist</span>';
        }
        
        echo '</div>';
        echo '</div>';
        
        // Service Options Table Analysis
        echo '<div class="debug-section">';
        echo '<div class="debug-section-header" onclick="toggleSection(this)">';
        echo '<span>⚙️ Service Options Table</span>';
        echo '<span class="debug-arrow">▶</span>';
        echo '</div>';
        echo '<div class="debug-section-content">';
        
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        $options_exists = $wpdb->get_var("SHOW TABLES LIKE '$options_table'") == $options_table;
        
        if ($options_exists) {
            // Get table structure
            $options_columns = $wpdb->get_results("SHOW COLUMNS FROM $options_table");
            $options_count = $wpdb->get_var("SELECT COUNT(*) FROM $options_table");
            
            // Get options count by service
            $user_options_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $options_table o 
                 JOIN $services_table s ON o.service_id = s.id 
                 WHERE s.user_id = %d", 
                get_current_user_id()
            ));
            
            echo '<div class="debug-grid">';
            echo '<div class="debug-metric">';
            echo '<div class="debug-metric-value">' . $options_count . '</div>';
            echo '<div class="debug-metric-label">Total Options</div>';
            echo '</div>';
            echo '<div class="debug-metric">';
            echo '<div class="debug-metric-value">' . $user_options_count . '</div>';
            echo '<div class="debug-metric-label">Your Options</div>';
            echo '</div>';
            echo '</div>';
            
            // Table structure
            echo '<h4>📋 Table Structure:</h4>';
            echo '<div class="debug-scrollable">';
            echo '<table class="debug-table">';
            echo '<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead><tbody>';
            foreach ($options_columns as $column) {
                echo '<tr>';
                echo '<td>' . esc_html($column->Field) . '</td>';
                echo '<td>' . esc_html($column->Type) . '</td>';
                echo '<td>' . esc_html($column->Null) . '</td>';
                echo '<td>' . esc_html($column->Key) . '</td>';
                echo '<td>' . esc_html($column->Default) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
            
            // Recent options
            $recent_options = $wpdb->get_results($wpdb->prepare(
                "SELECT o.id, o.name, o.type, o.is_required, o.price_impact, s.name as service_name, o.created_at 
                 FROM $options_table o 
                 JOIN $services_table s ON o.service_id = s.id 
                 WHERE s.user_id = %d 
                 ORDER BY o.created_at DESC LIMIT 5",
                get_current_user_id()
            ));
            
            if ($recent_options) {
                echo '<h4>🔧 Recent Options:</h4>';
                echo '<div class="debug-scrollable">';
                echo '<table class="debug-table">';
                echo '<thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Required</th><th>Price Impact</th><th>Service</th><th>Created</th></tr></thead><tbody>';
                foreach ($recent_options as $option) {
                    echo '<tr>';
                    echo '<td>' . esc_html($option->id) . '</td>';
                    echo '<td>' . esc_html($option->name) . '</td>';
                    echo '<td><span class="debug-status status-info">' . esc_html($option->type) . '</span></td>';
                    echo '<td>' . ($option->is_required ? '✅' : '❌') . '</td>';
                    echo '<td>$' . esc_html($option->price_impact) . '</td>';
                    echo '<td>' . esc_html($option->service_name) . '</td>';
                    echo '<td>' . esc_html($option->created_at) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                echo '</div>';
            }
        } else {
            echo '<span class="debug-status status-error">❌ Table does not exist</span>';
        }
        
        echo '</div>';
        echo '</div>';
        
        // Migration Status
        echo '<div class="debug-section">';
        echo '<div class="debug-section-header" onclick="toggleSection(this)">';
        echo '<span>🔄 Migration Status</span>';
        echo '<span class="debug-arrow">▶</span>';
        echo '</div>';
        echo '<div class="debug-section-content">';
        
        // Check for old unified table data
        $old_options_count = 0;
        if ($services_exists) {
            $old_options_count = $wpdb->get_var("SELECT COUNT(*) FROM $services_table WHERE entity_type = 'option'");
        }
        
        $migration_needed = $old_options_count > 0;
        
        echo '<div class="debug-grid">';
        echo '<div class="debug-metric">';
        echo '<div class="debug-metric-value">' . ($migration_needed ? '⚠️' : '✅') . '</div>';
        echo '<div class="debug-metric-label">Migration Status</div>';
        echo '</div>';
        echo '<div class="debug-metric">';
        echo '<div class="debug-metric-value">' . $old_options_count . '</div>';
        echo '<div class="debug-metric-label">Old Options</div>';
        echo '</div>';
        echo '</div>';
        
        if ($migration_needed) {
            echo '<div class="debug-code">';
            echo '⚠️ Migration Required: Found ' . $old_options_count . ' options in unified table<br>';
            echo 'Run migration to move data to separate options table';
            echo '</div>';
        } else {
            echo '<div class="debug-code">';
            echo '✅ Migration Complete: All data in separate tables';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        
        // AJAX Endpoints
        echo '<div class="debug-section">';
        echo '<div class="debug-section-header" onclick="toggleSection(this)">';
        echo '<span>🌐 AJAX Endpoints</span>';
        echo '<span class="debug-arrow">▶</span>';
        echo '</div>';
        echo '<div class="debug-section-content">';
        
        $endpoints = array(
            'mobooking_save_service' => 'Save Service',
            'mobooking_delete_service' => 'Delete Service',
            'mobooking_get_service' => 'Get Service',
            'mobooking_save_service_option' => 'Save Option',
            'mobooking_get_service_option' => 'Get Option',
            'mobooking_get_service_options' => 'Get Options',
            'mobooking_delete_service_option' => 'Delete Option',
            'mobooking_update_options_order' => 'Update Order'
        );
        
        echo '<table class="debug-table">';
        echo '<thead><tr><th>Endpoint</th><th>Description</th><th>Status</th></tr></thead><tbody>';
        foreach ($endpoints as $action => $description) {
            $handler_exists = has_action('wp_ajax_' . $action);
            echo '<tr>';
            echo '<td><code>' . esc_html($action) . '</code></td>';
            echo '<td>' . esc_html($description) . '</td>';
            echo '<td><span class="debug-status ' . ($handler_exists ? 'status-success' : 'status-error') . '">' . ($handler_exists ? '✅ Active' : '❌ Missing') . '</span></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        
        echo '</div>';
        echo '</div>';
        
        // Current Service Data (if editing)
        if ($current_view === 'edit' && $service_id && $service) {
            echo '<div class="debug-section">';
            echo '<div class="debug-section-header" onclick="toggleSection(this)">';
            echo '<span>📝 Current Service Data</span>';
            echo '<span class="debug-arrow">▶</span>';
            echo '</div>';
            echo '<div class="debug-section-content">';
            
            echo '<h4>🏢 Service Details:</h4>';
            echo '<div class="debug-json">';
            echo json_encode(array(
                'id' => $service->id,
                'name' => $service->name,
                'price' => $service->price,
                'duration' => $service->duration,
                'category' => $service->category,
                'status' => $service->status,
                'has_options' => isset($service->options) ? count($service->options) : 0
            ), JSON_PRETTY_PRINT);
            echo '</div>';
            
            if (isset($service->options) && !empty($service->options)) {
                echo '<h4>⚙️ Service Options:</h4>';
                echo '<div class="debug-json">';
                echo json_encode($service->options, JSON_PRETTY_PRINT);
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        // Database Queries Log (if SAVEQUERIES is enabled)
        if (defined('SAVEQUERIES') && SAVEQUERIES && !empty($wpdb->queries)) {
            echo '<div class="debug-section">';
            echo '<div class="debug-section-header" onclick="toggleSection(this)">';
            echo '<span>📊 Recent Queries (' . count($wpdb->queries) . ')</span>';
            echo '<span class="debug-arrow">▶</span>';
            echo '</div>';
            echo '<div class="debug-section-content">';
            
            $recent_queries = array_slice($wpdb->queries, -10); // Last 10 queries
            
            echo '<div class="debug-scrollable">';
            echo '<table class="debug-table">';
            echo '<thead><tr><th>Query</th><th>Time</th><th>Stack</th></tr></thead><tbody>';
            foreach ($recent_queries as $query) {
                echo '<tr>';
                echo '<td><code>' . esc_html($query[0]) . '</code></td>';
                echo '<td>' . esc_html($query[1]) . 's</td>';
                echo '<td>' . esc_html($query[2]) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
            
            echo '</div>';
            echo '</div>';
        }
        
        // System Information
        echo '<div class="debug-section">';
        echo '<div class="debug-section-header" onclick="toggleSection(this)">';
        echo '<span>🖥️ System Info</span>';
        echo '<span class="debug-arrow">▶</span>';
        echo '</div>';
        echo '<div class="debug-section-content">';
        
        echo '<div class="debug-grid">';
        echo '<div class="debug-metric">';
        echo '<div class="debug-metric-value">' . esc_html(get_bloginfo('version')) . '</div>';
        echo '<div class="debug-metric-label">WordPress</div>';
        echo '</div>';
        echo '<div class="debug-metric">';
        echo '<div class="debug-metric-value">' . esc_html(phpversion()) . '</div>';
        echo '<div class="debug-metric-label">PHP Version</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="debug-code">';
        echo 'Memory Usage: ' . size_format(memory_get_usage(true)) . '<br>';
        echo 'Memory Limit: ' . ini_get('memory_limit') . '<br>';
        echo 'Max Execution Time: ' . ini_get('max_execution_time') . 's<br>';
        echo 'Upload Max Size: ' . ini_get('upload_max_filesize');
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        ?>
    </div>
</div>

<script>
// Debug box functionality
function toggleDebugContent() {
    const content = document.getElementById('debug-content');
    content.style.display = content.style.display === 'none' ? 'block' : 'none';
}

function toggleSection(header) {
    const section = header.closest('.debug-section');
    section.classList.toggle('active');
}

// Make debug box draggable
let isDragging = false;
let currentX;
let currentY;
let initialX;
let initialY;
let xOffset = 0;
let yOffset = 0;

const debugBox = document.getElementById('mobooking-debug');
const dragHandle = debugBox.querySelector('.debug-header');

dragHandle.addEventListener('mousedown', dragStart);
document.addEventListener('mousemove', drag);
document.addEventListener('mouseup', dragEnd);

function dragStart(e) {
    initialX = e.clientX - xOffset;
    initialY = e.clientY - yOffset;

    if (e.target === dragHandle || dragHandle.contains(e.target)) {
        isDragging = true;
        debugBox.style.cursor = 'grabbing';
    }
}

function drag(e) {
    if (isDragging) {
        e.preventDefault();
        currentX = e.clientX - initialX;
        currentY = e.clientY - initialY;

        xOffset = currentX;
        yOffset = currentY;

        debugBox.style.transform = `translate(${currentX}px, ${currentY}px)`;
    }
}

function dragEnd(e) {
    initialX = currentX;
    initialY = currentY;
    isDragging = false;
    debugBox.style.cursor = 'default';
}

// Auto-refresh debug data every 30 seconds
setInterval(function() {
    const timestamps = document.querySelectorAll('.debug-timestamp');
    timestamps.forEach(function(timestamp) {
        timestamp.textContent = 'Updated: ' + new Date().toLocaleString();
    });
}, 30000);

// Add keyboard shortcut to toggle debug box (Ctrl+Shift+D)
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.shiftKey && e.key === 'D') {
        e.preventDefault();
        debugBox.style.display = debugBox.style.display === 'none' ? 'block' : 'none';
    }
});

// Console integration
console.log('🐛 MoBooking Debug Console Loaded');
console.log('📋 Use Ctrl+Shift+D to toggle debug box');
console.log('🔍 Debug data refreshes every 30 seconds');

// Make debug data available in console
window.MoBookingDebug = {
    toggleBox: toggleDebugContent,
    refreshData: function() {
        location.reload();
    },
    exportData: function() {
        const debugData = {};
        document.querySelectorAll('.debug-json').forEach(function(element, index) {
            debugData['section_' + index] = JSON.parse(element.textContent);
        });
        console.log('📊 Debug Data:', debugData);
        return debugData;
    }
};
</script>

<?php endif; ?>

