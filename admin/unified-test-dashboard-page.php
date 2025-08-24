<?php
/**
 * Unified Test Dashboard for Real Treasury Business Case Builder plugin.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check if user has required permissions
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have sufficient permissions to access this page.', 'rtbcb' ) );
}

// Get current system status
$api_key       = get_option( 'rtbcb_openai_api_key', '' );
$api_configured = ! empty( $api_key );
$api_valid     = $api_configured && rtbcb_is_valid_openai_api_key( $api_key );
$company_data  = rtbcb_get_current_company();
$has_company_data = ! empty( $company_data );

// Available models for testing
$available_models = [
    'mini'     => get_option( 'rtbcb_mini_model', 'gpt-4o-mini' ),
    'premium'  => get_option( 'rtbcb_premium_model', 'gpt-4o' ),
    'advanced' => get_option( 'rtbcb_advanced_model', 'o1-preview' ),
];
?>

<div class="wrap rtbcb-unified-test-dashboard">
    <div class="rtbcb-dashboard-header">
        <h1><?php esc_html_e( 'Unified Test Dashboard', 'rtbcb' ); ?></h1>
        <p class="rtbcb-dashboard-subtitle">
            <?php esc_html_e( 'Comprehensive testing suite for all plugin functionality', 'rtbcb' ); ?>
        </p>
        
        <!-- System Status Indicators -->
        <div class="rtbcb-system-status-bar">
            <div class="rtbcb-status-indicator <?php echo esc_attr( $api_valid ? 'status-good' : 'status-error' ); ?>">
                <span class="dashicons <?php echo esc_attr( $api_valid ? 'dashicons-yes-alt' : 'dashicons-warning' ); ?>"></span>
                <span><?php esc_html_e( 'OpenAI API', 'rtbcb' ); ?></span>
            </div>
            <div class="rtbcb-status-indicator <?php echo esc_attr( $has_company_data ? 'status-good' : 'status-warning' ); ?>">
                <span class="dashicons <?php echo esc_attr( $has_company_data ? 'dashicons-yes-alt' : 'dashicons-info' ); ?>"></span>
                <span><?php esc_html_e( 'Company Data', 'rtbcb' ); ?></span>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="rtbcb-test-tabs">
        <nav class="nav-tab-wrapper wp-clearfix">
            <a href="#company-overview" class="nav-tab nav-tab-active" data-tab="company-overview">
                <span class="dashicons dashicons-building"></span>
                <?php esc_html_e( 'Company Overview', 'rtbcb' ); ?>
            </a>
            <a href="#roi-calculator" class="nav-tab" data-tab="roi-calculator">
                <span class="dashicons dashicons-calculator"></span>
                <?php esc_html_e( 'ROI Calculator', 'rtbcb' ); ?>
            </a>
            <a href="#llm-integration" class="nav-tab" data-tab="llm-integration">
                <span class="dashicons dashicons-admin-network"></span>
                <?php esc_html_e( 'LLM Integration', 'rtbcb' ); ?>
            </a>
            <a href="#rag-system" class="nav-tab" data-tab="rag-system">
                <span class="dashicons dashicons-search"></span>
                <?php esc_html_e( 'RAG System', 'rtbcb' ); ?>
            </a>
            <a href="#api-health" class="nav-tab" data-tab="api-health">
                <span class="dashicons dashicons-cloud"></span>
                <?php esc_html_e( 'API Health', 'rtbcb' ); ?>
            </a>
        </nav>
    </div>

    <!-- Company Overview Test Section -->
    <div id="company-overview" class="rtbcb-test-section active">
        <div class="rtbcb-test-panel">
            <div class="rtbcb-panel-header">
                <h2><?php esc_html_e( 'Company Overview Testing', 'rtbcb' ); ?></h2>
                <p><?php esc_html_e( 'Generate AI-powered company analysis with detailed monitoring and debugging', 'rtbcb' ); ?></p>
            </div>

            <div class="rtbcb-test-controls">
                <div class="rtbcb-control-group">
                    <label for="company-name-input">
                        <?php esc_html_e( 'Company Name:', 'rtbcb' ); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="company-name-input" class="regular-text" 
                           placeholder="<?php esc_attr_e( 'Enter company name to analyze...', 'rtbcb' ); ?>" />
                </div>

                <div class="rtbcb-control-group">
                    <label for="model-selection"><?php esc_html_e( 'Model Selection:', 'rtbcb' ); ?></label>
                    <select id="model-selection">
                        <?php foreach ( $available_models as $key => $model ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>">
                                <?php echo esc_html( ucfirst( $key ) . ' (' . $model . ')' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="rtbcb-control-group">
                    <label>
                        <input type="checkbox" id="show-debug-info" />
                        <?php esc_html_e( 'Show debug information', 'rtbcb' ); ?>
                    </label>
                </div>

                <div class="rtbcb-action-buttons">
                    <button type="button" id="generate-company-overview" class="button button-primary" <?php disabled( ! $api_valid ); ?>>
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e( 'Generate Overview', 'rtbcb' ); ?>
                    </button>
                    <button type="button" id="clear-results" class="button">
                        <?php esc_html_e( 'Clear Results', 'rtbcb' ); ?>
                    </button>
                    <button type="button" id="export-results" class="button" disabled>
                        <?php esc_html_e( 'Export Results', 'rtbcb' ); ?>
                    </button>
                </div>
            </div>

            <!-- Progress Tracking -->
            <div id="progress-container" class="rtbcb-progress-container" style="display: none;">
                <div class="rtbcb-progress-header">
                    <h3><?php esc_html_e( 'Generation Progress', 'rtbcb' ); ?></h3>
                    <span id="progress-timer" class="rtbcb-progress-timer">00:00</span>
                </div>
                <div class="rtbcb-progress-bar">
                    <div id="progress-fill" class="rtbcb-progress-fill"></div>
                </div>
                <div id="progress-status" class="rtbcb-progress-status">
                    <?php esc_html_e( 'Initializing...', 'rtbcb' ); ?>
                </div>
            </div>

            <!-- Debug Information Panel -->
            <div id="debug-panel" class="rtbcb-debug-panel" style="display: none;">
                <div class="rtbcb-debug-header">
                    <h3><?php esc_html_e( 'Debug Information', 'rtbcb' ); ?></h3>
                    <button type="button" id="toggle-debug" class="button button-small">
                        <?php esc_html_e( 'Toggle', 'rtbcb' ); ?>
                    </button>
                </div>
                <div class="rtbcb-debug-content">
                    <div class="rtbcb-debug-section">
                        <h4><?php esc_html_e( 'System Prompt', 'rtbcb' ); ?></h4>
                        <pre id="system-prompt" class="rtbcb-code-block"></pre>
                    </div>
                    <div class="rtbcb-debug-section">
                        <h4><?php esc_html_e( 'User Prompt', 'rtbcb' ); ?></h4>
                        <pre id="user-prompt" class="rtbcb-code-block"></pre>
                    </div>
                    <div class="rtbcb-debug-section">
                        <h4><?php esc_html_e( 'API Request', 'rtbcb' ); ?></h4>
                        <pre id="api-request" class="rtbcb-code-block"></pre>
                    </div>
                    <div class="rtbcb-debug-section">
                        <h4><?php esc_html_e( 'Performance Metrics', 'rtbcb' ); ?></h4>
                        <div id="performance-metrics" class="rtbcb-metrics-grid">
                            <div class="metric">
                                <span class="metric-label"><?php esc_html_e( 'Response Time:', 'rtbcb' ); ?></span>
                                <span id="response-time" class="metric-value">--</span>
                            </div>
                            <div class="metric">
                                <span class="metric-label"><?php esc_html_e( 'Tokens Used:', 'rtbcb' ); ?></span>
                                <span id="tokens-used" class="metric-value">--</span>
                            </div>
                            <div class="metric">
                                <span class="metric-label"><?php esc_html_e( 'Word Count:', 'rtbcb' ); ?></span>
                                <span id="word-count" class="metric-value">--</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Display -->
            <div id="results-container" class="rtbcb-results-container" style="display: none;">
                <div class="rtbcb-results-header">
                    <h3><?php esc_html_e( 'Analysis Results', 'rtbcb' ); ?></h3>
                    <div class="rtbcb-results-actions">
                        <button type="button" id="copy-results" class="button button-small">
                            <span class="dashicons dashicons-admin-page"></span>
                            <?php esc_html_e( 'Copy', 'rtbcb' ); ?>
                        </button>
                        <button type="button" id="regenerate-results" class="button button-small">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e( 'Regenerate', 'rtbcb' ); ?>
                        </button>
                    </div>
                </div>
                <div id="results-content" class="rtbcb-results-content"></div>
                <div id="results-meta" class="rtbcb-results-meta"></div>
            </div>

            <!-- Error Display -->
            <div id="error-container" class="rtbcb-error-container" style="display: none;">
                <div class="rtbcb-error-header">
                    <h3><?php esc_html_e( 'Error Details', 'rtbcb' ); ?></h3>
                    <button type="button" id="retry-request" class="button button-primary">
                        <?php esc_html_e( 'Retry', 'rtbcb' ); ?>
                    </button>
                </div>
                <div id="error-content" class="rtbcb-error-content"></div>
                <div id="error-debug" class="rtbcb-error-debug"></div>
            </div>
        </div>
    </div>

    <!-- ROI Calculator Test Section (Placeholder) -->
    <div id="roi-calculator" class="rtbcb-test-section" style="display: none;">
        <div class="rtbcb-test-panel">
            <div class="rtbcb-panel-header">
                <h2><?php esc_html_e( 'ROI Calculator Testing', 'rtbcb' ); ?></h2>
                <p><?php esc_html_e( 'Test ROI calculations with multiple scenarios and visual results', 'rtbcb' ); ?></p>
            </div>
            <div class="rtbcb-placeholder">
                <p><?php esc_html_e( 'ROI Calculator testing interface will be implemented here.', 'rtbcb' ); ?></p>
            </div>
        </div>
    </div>

    <!-- LLM Integration Test Section (Placeholder) -->
    <div id="llm-integration" class="rtbcb-test-section" style="display: none;">
        <div class="rtbcb-test-panel">
            <div class="rtbcb-panel-header">
                <h2><?php esc_html_e( 'LLM Integration Testing', 'rtbcb' ); ?></h2>
                <p><?php esc_html_e( 'Test different models, prompts, and response quality', 'rtbcb' ); ?></p>
            </div>
            <div class="rtbcb-placeholder">
                <p><?php esc_html_e( 'LLM Integration testing interface will be implemented here.', 'rtbcb' ); ?></p>
            </div>
        </div>
    </div>

    <!-- RAG System Test Section (Placeholder) -->
    <div id="rag-system" class="rtbcb-test-section" style="display: none;">
        <div class="rtbcb-test-panel">
            <div class="rtbcb-panel-header">
                <h2><?php esc_html_e( 'RAG System Testing', 'rtbcb' ); ?></h2>
                <p><?php esc_html_e( 'Test vector search, retrieval, and context validation', 'rtbcb' ); ?></p>
            </div>
            <div class="rtbcb-placeholder">
                <p><?php esc_html_e( 'RAG System testing interface will be implemented here.', 'rtbcb' ); ?></p>
            </div>
        </div>
    </div>

    <!-- API Health Test Section (Placeholder) -->
    <div id="api-health" class="rtbcb-test-section" style="display: none;">
        <div class="rtbcb-test-panel">
            <div class="rtbcb-panel-header">
                <h2><?php esc_html_e( 'API Health Testing', 'rtbcb' ); ?></h2>
                <p><?php esc_html_e( 'Monitor API connectivity, rate limits, and error handling', 'rtbcb' ); ?></p>
            </div>
            <div class="rtbcb-placeholder">
                <p><?php esc_html_e( 'API Health testing interface will be implemented here.', 'rtbcb' ); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Hidden elements for nonce and AJAX -->
<?php wp_nonce_field( 'rtbcb_unified_test_dashboard', 'rtbcb_unified_test_nonce' ); ?>
<input type="hidden" id="ajaxurl" value="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" />
