<?php
/**
 * Modern Settings & Testing View for Real Treasury Business Case Builder
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get current settings
$api_key = get_option( 'rtbcb_openai_api_key', '' );
$default_model = get_option( 'rtbcb_default_model', 'gpt-4o-mini' );
$enable_caching = get_option( 'rtbcb_enable_caching', false );
$debug_mode = get_option( 'rtbcb_debug_mode', false );

// Available models
$available_models = [
    'gpt-4o-mini' => 'GPT-4o Mini (Recommended)',
    'gpt-4o' => 'GPT-4o',
    'gpt-4' => 'GPT-4',
    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
];

// Test API connection
$api_status = 'unknown';
$api_error = '';
if ( ! empty( $api_key ) ) {
    // Simple check to see if key format is valid
    if ( strpos( $api_key, 'sk-' ) === 0 && strlen( $api_key ) > 20 ) {
        $api_status = 'configured';
    } else {
        $api_status = 'invalid';
        $api_error = __( 'API key format appears invalid', 'rtbcb' );
    }
}

// System information
$system_info = [
    'php_version' => PHP_VERSION,
    'wp_version' => get_bloginfo( 'version' ),
    'plugin_version' => RTBCB_VERSION,
    'memory_limit' => ini_get( 'memory_limit' ),
    'max_execution_time' => ini_get( 'max_execution_time' ),
    'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
];

// Database info
global $wpdb;
$leads_table = $wpdb->prefix . 'rtbcb_leads';
$rag_table = $wpdb->prefix . 'rtbcb_rag_index';
$leads_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$leads_table}" );
$rag_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$rag_table}" );
?>

<div class="rtbcb-admin-page rtbcb-settings-page">
    <div class="rtbcb-page-header">
        <h1><?php esc_html_e( 'Settings & Testing', 'rtbcb' ); ?></h1>
        <div class="rtbcb-page-actions">
            <button class="rtbcb-btn rtbcb-btn-outline" data-action="export-settings">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e( 'Export Settings', 'rtbcb' ); ?>
            </button>
            <button class="rtbcb-btn rtbcb-btn-primary rtbcb-test-api-btn" data-action="test-api">
                <span class="dashicons dashicons-cloud"></span>
                <?php esc_html_e( 'Test API', 'rtbcb' ); ?>
            </button>
        </div>
    </div>

    <div class="rtbcb-settings-grid">
        <!-- API Configuration -->
        <div class="rtbcb-card">
            <div class="rtbcb-card-header">
                <h2 class="rtbcb-card-title"><?php esc_html_e( 'OpenAI API Configuration', 'rtbcb' ); ?></h2>
                <p class="rtbcb-card-subtitle"><?php esc_html_e( 'Configure your OpenAI API connection for LLM functionality', 'rtbcb' ); ?></p>
            </div>
            
            <form method="post" action="" class="rtbcb-ajax-form" data-action="save_settings">
                <?php wp_nonce_field( 'rtbcb_save_settings', 'rtbcb_settings_nonce' ); ?>
                <input type="hidden" name="rtbcb_save_settings" value="1">
                
                <div class="rtbcb-form-group">
                    <label for="rtbcb_openai_api_key" class="rtbcb-form-label">
                        <?php esc_html_e( 'OpenAI API Key', 'rtbcb' ); ?>
                        <span class="rtbcb-required">*</span>
                    </label>
                    <div class="rtbcb-input-group">
                        <input 
                            type="password" 
                            id="rtbcb_openai_api_key" 
                            name="rtbcb_openai_api_key" 
                            value="<?php echo esc_attr( $api_key ); ?>"
                            class="rtbcb-form-input"
                            placeholder="sk-..."
                            autocomplete="off"
                        >
                        <button type="button" class="rtbcb-btn rtbcb-btn-outline rtbcb-btn-small rtbcb-toggle-visibility" data-target="#rtbcb_openai_api_key">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                    </div>
                    <p class="rtbcb-form-help">
                        <?php 
                        printf(
                            /* translators: %s: OpenAI API keys URL */
                            esc_html__( 'Get your API key from %s', 'rtbcb' ),
                            '<a href="https://platform.openai.com/api-keys" target="_blank">' . esc_html__( 'OpenAI Platform', 'rtbcb' ) . '</a>'
                        );
                        ?>
                    </p>
                    <?php if ( $api_status === 'configured' ) : ?>
                        <div class="rtbcb-status-indicator rtbcb-status-success">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e( 'API key is configured', 'rtbcb' ); ?>
                        </div>
                    <?php elseif ( $api_status === 'invalid' ) : ?>
                        <div class="rtbcb-status-indicator rtbcb-status-error">
                            <span class="dashicons dashicons-warning"></span>
                            <?php echo esc_html( $api_error ); ?>
                        </div>
                    <?php else : ?>
                        <div class="rtbcb-status-indicator rtbcb-status-warning">
                            <span class="dashicons dashicons-info"></span>
                            <?php esc_html_e( 'API key not configured', 'rtbcb' ); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="rtbcb-form-group">
                    <label for="rtbcb_default_model" class="rtbcb-form-label">
                        <?php esc_html_e( 'Default AI Model', 'rtbcb' ); ?>
                    </label>
                    <select id="rtbcb_default_model" name="rtbcb_default_model" class="rtbcb-form-select">
                        <?php foreach ( $available_models as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $default_model, $value ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="rtbcb-form-help">
                        <?php esc_html_e( 'Choose the AI model for generating business case narratives. GPT-4o Mini is recommended for cost-effectiveness.', 'rtbcb' ); ?>
                    </p>
                </div>
                
                <div class="rtbcb-form-group">
                    <label class="rtbcb-checkbox-label">
                        <input type="checkbox" name="rtbcb_enable_caching" value="1" <?php checked( $enable_caching ); ?>>
                        <span class="rtbcb-checkbox-text"><?php esc_html_e( 'Enable Response Caching', 'rtbcb' ); ?></span>
                    </label>
                    <p class="rtbcb-form-help">
                        <?php esc_html_e( 'Cache AI responses to reduce API costs and improve performance for similar inputs.', 'rtbcb' ); ?>
                    </p>
                </div>
                
                <div class="rtbcb-form-group">
                    <label class="rtbcb-checkbox-label">
                        <input type="checkbox" name="rtbcb_debug_mode" value="1" <?php checked( $debug_mode ); ?>>
                        <span class="rtbcb-checkbox-text"><?php esc_html_e( 'Enable Debug Mode', 'rtbcb' ); ?></span>
                    </label>
                    <p class="rtbcb-form-help">
                        <?php esc_html_e( 'Enable detailed logging for troubleshooting. Only enable when needed.', 'rtbcb' ); ?>
                    </p>
                </div>
                
                <div class="rtbcb-form-actions">
                    <button type="submit" class="rtbcb-btn rtbcb-btn-primary">
                        <span class="dashicons dashicons-yes"></span>
                        <?php esc_html_e( 'Save Settings', 'rtbcb' ); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Testing Dashboard -->
        <div class="rtbcb-card">
            <div class="rtbcb-card-header">
                <h2 class="rtbcb-card-title"><?php esc_html_e( 'Testing Dashboard', 'rtbcb' ); ?></h2>
                <p class="rtbcb-card-subtitle"><?php esc_html_e( 'Test plugin functionality and troubleshoot issues', 'rtbcb' ); ?></p>
            </div>
            
            <div class="rtbcb-testing-sections">
                <!-- API Tests -->
                <div class="rtbcb-test-section">
                    <h3><?php esc_html_e( 'API Connection Tests', 'rtbcb' ); ?></h3>
                    <div class="rtbcb-test-actions">
                        <button class="rtbcb-btn rtbcb-btn-outline rtbcb-btn-small" data-action="test-openai">
                            <span class="dashicons dashicons-cloud"></span>
                            <?php esc_html_e( 'Test OpenAI Connection', 'rtbcb' ); ?>
                        </button>
                        <button class="rtbcb-btn rtbcb-btn-outline rtbcb-btn-small" data-action="test-model">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php esc_html_e( 'Test Model Response', 'rtbcb' ); ?>
                        </button>
                    </div>
                    <div id="api-test-results" class="rtbcb-test-results" style="display: none;"></div>
                </div>
                
                <!-- Calculator Tests -->
                <div class="rtbcb-test-section">
                    <h3><?php esc_html_e( 'Calculator Tests', 'rtbcb' ); ?></h3>
                    <div class="rtbcb-test-actions">
                        <button class="rtbcb-btn rtbcb-btn-outline rtbcb-btn-small" data-action="test-calculator">
                            <span class="dashicons dashicons-calculator"></span>
                            <?php esc_html_e( 'Test ROI Calculator', 'rtbcb' ); ?>
                        </button>
                        <button class="rtbcb-btn rtbcb-btn-outline rtbcb-btn-small" data-action="test-scenarios">
                            <span class="dashicons dashicons-chart-line"></span>
                            <?php esc_html_e( 'Test Scenarios', 'rtbcb' ); ?>
                        </button>
                    </div>
                    <div id="calculator-test-results" class="rtbcb-test-results" style="display: none;"></div>
                </div>
                
                <!-- Database Tests -->
                <div class="rtbcb-test-section">
                    <h3><?php esc_html_e( 'Database Tests', 'rtbcb' ); ?></h3>
                    <div class="rtbcb-test-actions">
                        <button class="rtbcb-btn rtbcb-btn-outline rtbcb-btn-small" data-action="test-database">
                            <span class="dashicons dashicons-database"></span>
                            <?php esc_html_e( 'Test Database', 'rtbcb' ); ?>
                        </button>
                        <button class="rtbcb-btn rtbcb-btn-outline rtbcb-btn-small" data-action="rebuild-rag">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e( 'Rebuild RAG Index', 'rtbcb' ); ?>
                        </button>
                    </div>
                    <div id="database-test-results" class="rtbcb-test-results" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="rtbcb-card">
        <div class="rtbcb-card-header">
            <h2 class="rtbcb-card-title"><?php esc_html_e( 'System Information', 'rtbcb' ); ?></h2>
            <p class="rtbcb-card-subtitle"><?php esc_html_e( 'Environment details and compatibility information', 'rtbcb' ); ?></p>
        </div>
        
        <div class="rtbcb-system-grid">
            <!-- Environment Info -->
            <div class="rtbcb-system-section">
                <h3><?php esc_html_e( 'Environment', 'rtbcb' ); ?></h3>
                <div class="rtbcb-system-items">
                    <div class="rtbcb-system-item">
                        <span class="rtbcb-system-label"><?php esc_html_e( 'PHP Version:', 'rtbcb' ); ?></span>
                        <span class="rtbcb-system-value <?php echo version_compare( $system_info['php_version'], '7.4', '>=' ) ? 'rtbcb-status-success' : 'rtbcb-status-error'; ?>">
                            <?php echo esc_html( $system_info['php_version'] ); ?>
                        </span>
                    </div>
                    <div class="rtbcb-system-item">
                        <span class="rtbcb-system-label"><?php esc_html_e( 'WordPress Version:', 'rtbcb' ); ?></span>
                        <span class="rtbcb-system-value <?php echo version_compare( $system_info['wp_version'], '5.0', '>=' ) ? 'rtbcb-status-success' : 'rtbcb-status-error'; ?>">
                            <?php echo esc_html( $system_info['wp_version'] ); ?>
                        </span>
                    </div>
                    <div class="rtbcb-system-item">
                        <span class="rtbcb-system-label"><?php esc_html_e( 'Plugin Version:', 'rtbcb' ); ?></span>
                        <span class="rtbcb-system-value rtbcb-status-info">
                            <?php echo esc_html( $system_info['plugin_version'] ); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Server Limits -->
            <div class="rtbcb-system-section">
                <h3><?php esc_html_e( 'Server Limits', 'rtbcb' ); ?></h3>
                <div class="rtbcb-system-items">
                    <div class="rtbcb-system-item">
                        <span class="rtbcb-system-label"><?php esc_html_e( 'Memory Limit:', 'rtbcb' ); ?></span>
                        <span class="rtbcb-system-value">
                            <?php echo esc_html( $system_info['memory_limit'] ); ?>
                        </span>
                    </div>
                    <div class="rtbcb-system-item">
                        <span class="rtbcb-system-label"><?php esc_html_e( 'Max Execution Time:', 'rtbcb' ); ?></span>
                        <span class="rtbcb-system-value">
                            <?php echo esc_html( $system_info['max_execution_time'] ); ?>s
                        </span>
                    </div>
                    <div class="rtbcb-system-item">
                        <span class="rtbcb-system-label"><?php esc_html_e( 'Upload Max Size:', 'rtbcb' ); ?></span>
                        <span class="rtbcb-system-value">
                            <?php echo esc_html( $system_info['upload_max_filesize'] ); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Database Info -->
            <div class="rtbcb-system-section">
                <h3><?php esc_html_e( 'Database', 'rtbcb' ); ?></h3>
                <div class="rtbcb-system-items">
                    <div class="rtbcb-system-item">
                        <span class="rtbcb-system-label"><?php esc_html_e( 'Leads Table:', 'rtbcb' ); ?></span>
                        <span class="rtbcb-system-value rtbcb-status-success">
                            <?php echo esc_html( number_format( $leads_count ) ); ?> <?php esc_html_e( 'records', 'rtbcb' ); ?>
                        </span>
                    </div>
                    <div class="rtbcb-system-item">
                        <span class="rtbcb-system-label"><?php esc_html_e( 'RAG Index:', 'rtbcb' ); ?></span>
                        <span class="rtbcb-system-value <?php echo $rag_count > 0 ? 'rtbcb-status-success' : 'rtbcb-status-warning'; ?>">
                            <?php echo esc_html( number_format( $rag_count ) ); ?> <?php esc_html_e( 'entries', 'rtbcb' ); ?>
                        </span>
                    </div>
                    <div class="rtbcb-system-item">
                        <span class="rtbcb-system-label"><?php esc_html_e( 'Table Prefix:', 'rtbcb' ); ?></span>
                        <span class="rtbcb-system-value rtbcb-status-info">
                            <?php echo esc_html( $wpdb->prefix ); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="rtbcb-system-actions">
            <button class="rtbcb-btn rtbcb-btn-outline" data-action="copy-system-info">
                <span class="dashicons dashicons-clipboard"></span>
                <?php esc_html_e( 'Copy System Info', 'rtbcb' ); ?>
            </button>
            <button class="rtbcb-btn rtbcb-btn-outline" data-action="download-debug-log">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e( 'Download Debug Log', 'rtbcb' ); ?>
            </button>
        </div>
    </div>

    <!-- Quick Troubleshooting -->
    <div class="rtbcb-card">
        <div class="rtbcb-card-header">
            <h2 class="rtbcb-card-title"><?php esc_html_e( 'Quick Troubleshooting', 'rtbcb' ); ?></h2>
            <p class="rtbcb-card-subtitle"><?php esc_html_e( 'Common issues and solutions', 'rtbcb' ); ?></p>
        </div>
        
        <div class="rtbcb-troubleshooting-list">
            <details class="rtbcb-troubleshooting-item">
                <summary><?php esc_html_e( 'API Key Not Working', 'rtbcb' ); ?></summary>
                <div class="rtbcb-troubleshooting-content">
                    <p><?php esc_html_e( 'If your OpenAI API key is not working:', 'rtbcb' ); ?></p>
                    <ul>
                        <li><?php esc_html_e( 'Verify the key starts with "sk-" and is the correct length', 'rtbcb' ); ?></li>
                        <li><?php esc_html_e( 'Check your OpenAI account has sufficient credits', 'rtbcb' ); ?></li>
                        <li><?php esc_html_e( 'Ensure your server can make HTTPS requests to OpenAI', 'rtbcb' ); ?></li>
                        <li><?php esc_html_e( 'Try the "Test API Connection" button above', 'rtbcb' ); ?></li>
                    </ul>
                </div>
            </details>
            
            <details class="rtbcb-troubleshooting-item">
                <summary><?php esc_html_e( 'Business Case Not Generating', 'rtbcb' ); ?></summary>
                <div class="rtbcb-troubleshooting-content">
                    <p><?php esc_html_e( 'If business cases are not generating:', 'rtbcb' ); ?></p>
                    <ul>
                        <li><?php esc_html_e( 'Check that your API key is configured and working', 'rtbcb' ); ?></li>
                        <li><?php esc_html_e( 'Verify PHP memory limit is at least 128MB', 'rtbcb' ); ?></li>
                        <li><?php esc_html_e( 'Ensure max execution time is at least 60 seconds', 'rtbcb' ); ?></li>
                        <li><?php esc_html_e( 'Enable debug mode to see detailed error messages', 'rtbcb' ); ?></li>
                    </ul>
                </div>
            </details>
            
            <details class="rtbcb-troubleshooting-item">
                <summary><?php esc_html_e( 'Performance Issues', 'rtbcb' ); ?></summary>
                <div class="rtbcb-troubleshooting-content">
                    <p><?php esc_html_e( 'To improve performance:', 'rtbcb' ); ?></p>
                    <ul>
                        <li><?php esc_html_e( 'Enable response caching to reduce API calls', 'rtbcb' ); ?></li>
                        <li><?php esc_html_e( 'Use GPT-4o Mini model for faster responses', 'rtbcb' ); ?></li>
                        <li><?php esc_html_e( 'Rebuild the RAG index if searches are slow', 'rtbcb' ); ?></li>
                        <li><?php esc_html_e( 'Increase PHP memory limit if needed', 'rtbcb' ); ?></li>
                    </ul>
                </div>
            </details>
        </div>
    </div>
</div>

<style>
/* Settings page specific styles */
.rtbcb-settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 32px;
}

.rtbcb-input-group {
    display: flex;
    gap: 8px;
}

.rtbcb-input-group .rtbcb-form-input {
    flex: 1;
}

.rtbcb-toggle-visibility {
    padding: 8px 12px;
}

.rtbcb-status-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 14px;
    margin-top: 8px;
}

.rtbcb-status-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.rtbcb-status-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.rtbcb-status-warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fed7aa;
}

.rtbcb-status-info {
    color: #1e40af;
}

.rtbcb-required {
    color: #ef4444;
    margin-left: 4px;
}

.rtbcb-form-help {
    font-size: 13px;
    color: #64748b;
    margin-top: 6px;
    margin-bottom: 0;
}

.rtbcb-form-help a {
    color: #3b82f6;
    text-decoration: none;
}

.rtbcb-form-help a:hover {
    text-decoration: underline;
}

.rtbcb-checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.rtbcb-checkbox-text {
    font-weight: 500;
}

.rtbcb-testing-sections {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.rtbcb-test-section {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
}

.rtbcb-test-section h3 {
    margin: 0 0 16px 0;
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

.rtbcb-test-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
}

.rtbcb-test-results {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 16px;
    font-family: monospace;
    font-size: 13px;
    white-space: pre-wrap;
}

.rtbcb-system-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
    margin-bottom: 24px;
}

.rtbcb-system-section h3 {
    margin: 0 0 16px 0;
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 8px;
}

.rtbcb-system-items {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.rtbcb-system-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
}

.rtbcb-system-label {
    font-weight: 500;
    color: #374151;
}

.rtbcb-system-value {
    font-family: monospace;
    font-size: 13px;
    padding: 2px 6px;
    border-radius: 4px;
}

.rtbcb-system-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.rtbcb-troubleshooting-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.rtbcb-troubleshooting-item {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}

.rtbcb-troubleshooting-item summary {
    padding: 16px 20px;
    background: #f8fafc;
    cursor: pointer;
    font-weight: 500;
    user-select: none;
    border-bottom: 1px solid #e2e8f0;
}

.rtbcb-troubleshooting-item summary:hover {
    background: #f1f5f9;
}

.rtbcb-troubleshooting-content {
    padding: 20px;
}

.rtbcb-troubleshooting-content ul {
    margin: 12px 0;
    padding-left: 20px;
}

.rtbcb-troubleshooting-content li {
    margin-bottom: 8px;
    color: #64748b;
}

@media (max-width: 1200px) {
    .rtbcb-settings-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .rtbcb-page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .rtbcb-system-grid {
        grid-template-columns: 1fr;
    }
    
    .rtbcb-system-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
    
    .rtbcb-test-actions {
        flex-direction: column;
    }
    
    .rtbcb-system-actions {
        flex-direction: column;
    }
}
</style>