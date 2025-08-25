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

// Enqueue dashboard assets
function rtbcb_enqueue_dashboard_assets() {
    $css_file = RTBCB_URL . 'admin/css/unified-test-dashboard.css';
    $js_file  = RTBCB_URL . 'admin/js/unified-test-dashboard.js';

    wp_enqueue_style(
        'rtbcb-unified-dashboard',
        $css_file,
        [],
        filemtime( RTBCB_DIR . 'admin/css/unified-test-dashboard.css' )
    );

    wp_enqueue_script(
        'chart-js',
        'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
        [],
        '3.9.1',
        true
    );

    wp_enqueue_script(
        'rtbcb-unified-dashboard',
        $js_file,
        [ 'jquery', 'chart-js' ],
        filemtime( RTBCB_DIR . 'admin/js/unified-test-dashboard.js' ),
        true
    );

    wp_localize_script( 'rtbcb-unified-dashboard', 'rtbcbDashboard', [
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonces'  => [
            'dashboard'     => wp_create_nonce( 'rtbcb_unified_test_dashboard' ),
            'llm'           => wp_create_nonce( 'rtbcb_llm_testing' ),
            'apiHealth'     => wp_create_nonce( 'rtbcb_api_health_tests' ),
            'reportPreview' => wp_create_nonce( 'rtbcb_generate_preview_report' ),
            'dataHealth'    => wp_create_nonce( 'rtbcb_data_health_checks' ),
            'ragTesting'    => wp_create_nonce( 'rtbcb_rag_testing' ),
            'saveSettings'  => wp_create_nonce( 'rtbcb_save_dashboard_settings' ),
        ],
        'models'  => [
            'mini'     => get_option( 'rtbcb_mini_model', rtbcb_get_default_model( 'mini' ) ),
            'premium'  => get_option( 'rtbcb_premium_model', rtbcb_get_default_model( 'premium' ) ),
            'advanced' => get_option( 'rtbcb_advanced_model', rtbcb_get_default_model( 'advanced' ) ),
        ],
        'features' => [
            'debugMode'                  => defined( 'WP_DEBUG' ) && WP_DEBUG,
            'lastSuccessfulOpenAIPingAt' => get_option( 'rtbcb_openai_last_ok', 0 ),
        ],
        'strings' => [
            'generating'    => __( 'Generating...', 'rtbcb' ),
            'complete'      => __( 'Complete!', 'rtbcb' ),
            'error'         => __( 'Error occurred', 'rtbcb' ),
            'settingsSaved' => __( 'Settings saved successfully', 'rtbcb' ),
            'running'       => __( 'Running...', 'rtbcb' ),
            'retrieving'    => __( 'Retrieving...', 'rtbcb' ),
            'noResults'     => __( 'No results found', 'rtbcb' ),
            'lastIndexed'   => __( 'Last indexed: %s', 'rtbcb' ),
            'entries'       => __( 'Entries: %d', 'rtbcb' ),
            'indexRebuilt'  => __( 'Index rebuilt successfully', 'rtbcb' ),
            'rebuildFailed' => __( 'Index rebuild failed', 'rtbcb' ),
            'notTested'     => __( 'Not tested', 'rtbcb' ),
            'allOperational'=> __( 'All systems operational', 'rtbcb' ),
            'errorsDetected'=> __( 'Errors detected: %d', 'rtbcb' ),
        ],
    ] );
}

rtbcb_enqueue_dashboard_assets();

// Get current system status
$api_key       = get_option( 'rtbcb_openai_api_key', '' );
$api_configured = ! empty( $api_key );
$api_valid     = $api_configured && rtbcb_is_valid_openai_api_key( $api_key );
$company_data  = rtbcb_get_current_company();
$has_company_data = ! empty( $company_data );

// Available models for testing
$available_models = [
    'mini'     => get_option( 'rtbcb_mini_model', rtbcb_get_default_model( 'mini' ) ),
    'premium'  => get_option( 'rtbcb_premium_model', rtbcb_get_default_model( 'premium' ) ),
    'advanced' => get_option( 'rtbcb_advanced_model', rtbcb_get_default_model( 'advanced' ) ),
];

// Settings values
$mini_model     = $available_models['mini'];
$premium_model  = $available_models['premium'];
$advanced_model = $available_models['advanced'];
$embedding_model = get_option( 'rtbcb_embedding_model', rtbcb_get_default_model( 'embedding' ) );

$all_models = rtbcb_get_available_models();
$chat_models = [];
$embedding_models = [];

foreach ( $all_models as $model ) {
    if ( false !== strpos( $model, 'embedding' ) ) {
        $embedding_models[ $model ] = $model;
    } else {
        $chat_models[ $model ] = $model;
    }
}

if ( empty( $chat_models ) ) {
    $chat_models = [
        $mini_model     => $mini_model,
        $premium_model  => $premium_model,
        $advanced_model => $advanced_model,
    ];
}

if ( empty( $embedding_models ) ) {
    $embedding_models = [ $embedding_model => $embedding_model ];
}

// RAG index information
global $wpdb;
$last_indexed = get_option( 'rtbcb_last_indexed', '' );
$index_size   = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'rtbcb_rag_index' );
$portal_active = (bool) ( has_filter( 'rt_portal_get_vendors' ) || has_filter( 'rt_portal_get_vendor_notes' ) );
$last_index_display = $last_indexed ? $last_indexed : __( 'Never', 'rtbcb' );
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
            <a href="#llm-tests" class="nav-tab" data-tab="llm-tests">
                <span class="dashicons dashicons-admin-network"></span>
                <?php esc_html_e( 'LLM Tests', 'rtbcb' ); ?>
            </a>
            <a href="#rag-system" class="nav-tab" data-tab="rag-system">
                <span class="dashicons dashicons-search"></span>
                <?php esc_html_e( 'RAG System', 'rtbcb' ); ?>
            </a>
            <a href="#api-health" class="nav-tab" data-tab="api-health">
                <span class="dashicons dashicons-cloud"></span>
                <?php esc_html_e( 'API Health', 'rtbcb' ); ?>
            </a>
            <a href="#data-health" class="nav-tab" data-tab="data-health">
                <span class="dashicons dashicons-heart"></span>
                <?php esc_html_e( 'Data Health', 'rtbcb' ); ?>
            </a>
            <a href="#report-preview" class="nav-tab" data-tab="report-preview">
                <span class="dashicons dashicons-media-document"></span>
                <?php esc_html_e( 'Report Preview', 'rtbcb' ); ?>
            </a>
            <a href="#settings" class="nav-tab" data-tab="settings">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php esc_html_e( 'Settings', 'rtbcb' ); ?>
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

    <!-- ROI Calculator Test Section -->
    <div id="roi-calculator" class="rtbcb-test-section" style="display: none;">
        <div class="rtbcb-test-panel">
            <div class="rtbcb-panel-header">
                <h2><?php esc_html_e( 'ROI Calculator Testing', 'rtbcb' ); ?></h2>
                <p><?php esc_html_e( 'Test ROI calculations with multiple scenarios, visual charts, and detailed analysis', 'rtbcb' ); ?></p>
            </div>

            <div class="rtbcb-test-controls">
                <div class="rtbcb-roi-scenarios">
                    <h3><?php esc_html_e( 'Test Scenarios', 'rtbcb' ); ?></h3>
                    <div class="rtbcb-scenario-tabs">
                        <button type="button" class="rtbcb-scenario-tab active" data-scenario="custom">
                            <?php esc_html_e( 'Custom Input', 'rtbcb' ); ?>
                        </button>
                        <button type="button" class="rtbcb-scenario-tab" data-scenario="small-company">
                            <?php esc_html_e( 'Small Company', 'rtbcb' ); ?>
                        </button>
                        <button type="button" class="rtbcb-scenario-tab" data-scenario="medium-company">
                            <?php esc_html_e( 'Medium Company', 'rtbcb' ); ?>
                        </button>
                        <button type="button" class="rtbcb-scenario-tab" data-scenario="large-company">
                            <?php esc_html_e( 'Large Company', 'rtbcb' ); ?>
                        </button>
                    </div>
                </div>

                <div class="rtbcb-roi-input-grid">
                    <!-- Company Information -->
                    <div class="rtbcb-input-section">
                        <h4><?php esc_html_e( 'Company Information', 'rtbcb' ); ?></h4>

                        <div class="rtbcb-control-group">
                            <label for="roi-company-size"><?php esc_html_e( 'Company Size:', 'rtbcb' ); ?></label>
                            <select id="roi-company-size">
                                <option value="startup"><?php esc_html_e( 'Startup (1-50 employees)', 'rtbcb' ); ?></option>
                                <option value="small"><?php esc_html_e( 'Small (51-200 employees)', 'rtbcb' ); ?></option>
                                <option value="medium" selected><?php esc_html_e( 'Medium (201-1000 employees)', 'rtbcb' ); ?></option>
                                <option value="large"><?php esc_html_e( 'Large (1001-5000 employees)', 'rtbcb' ); ?></option>
                                <option value="enterprise"><?php esc_html_e( 'Enterprise (5000+ employees)', 'rtbcb' ); ?></option>
                            </select>
                        </div>

                        <div class="rtbcb-control-group">
                            <label for="roi-annual-revenue"><?php esc_html_e( 'Annual Revenue ($):', 'rtbcb' ); ?></label>
                            <input type="number" id="roi-annual-revenue" min="0" step="1000000" value="50000000" />
                            <span class="rtbcb-input-helper">$50M</span>
                        </div>

                        <div class="rtbcb-control-group">
                            <label for="roi-industry"><?php esc_html_e( 'Industry:', 'rtbcb' ); ?></label>
                            <select id="roi-industry">
                                <option value="manufacturing"><?php esc_html_e( 'Manufacturing', 'rtbcb' ); ?></option>
                                <option value="technology"><?php esc_html_e( 'Technology', 'rtbcb' ); ?></option>
                                <option value="healthcare"><?php esc_html_e( 'Healthcare', 'rtbcb' ); ?></option>
                                <option value="financial-services"><?php esc_html_e( 'Financial Services', 'rtbcb' ); ?></option>
                                <option value="retail"><?php esc_html_e( 'Retail', 'rtbcb' ); ?></option>
                                <option value="energy"><?php esc_html_e( 'Energy', 'rtbcb' ); ?></option>
                                <option value="other"><?php esc_html_e( 'Other', 'rtbcb' ); ?></option>
                            </select>
                        </div>
                    </div>

                    <!-- Treasury Operations -->
                    <div class="rtbcb-input-section">
                        <h4><?php esc_html_e( 'Treasury Operations', 'rtbcb' ); ?></h4>

                        <div class="rtbcb-control-group">
                            <label for="roi-treasury-staff"><?php esc_html_e( 'Treasury Staff Count:', 'rtbcb' ); ?></label>
                            <input type="number" id="roi-treasury-staff" min="1" max="100" value="5" />
                        </div>

                        <div class="rtbcb-control-group">
                            <label for="roi-avg-salary"><?php esc_html_e( 'Average Salary ($):', 'rtbcb' ); ?></label>
                            <input type="number" id="roi-avg-salary" min="0" step="1000" value="85000" />
                        </div>

                        <div class="rtbcb-control-group">
                            <label for="roi-hours-reconciliation"><?php esc_html_e( 'Daily Hours on Reconciliation:', 'rtbcb' ); ?></label>
                            <input type="number" id="roi-hours-reconciliation" min="0" max="24" step="0.5" value="4" />
                        </div>

                        <div class="rtbcb-control-group">
                            <label for="roi-hours-reporting"><?php esc_html_e( 'Daily Hours on Reporting:', 'rtbcb' ); ?></label>
                            <input type="number" id="roi-hours-reporting" min="0" max="24" step="0.5" value="2" />
                        </div>

                        <div class="rtbcb-control-group">
                            <label for="roi-hours-analysis"><?php esc_html_e( 'Daily Hours on Analysis:', 'rtbcb' ); ?></label>
                            <input type="number" id="roi-hours-analysis" min="0" max="24" step="0.5" value="3" />
                        </div>
                    </div>

                    <!-- Banking & Fees -->
                    <div class="rtbcb-input-section">
                        <h4><?php esc_html_e( 'Banking & Fees', 'rtbcb' ); ?></h4>

                        <div class="rtbcb-control-group">
                            <label for="roi-num-banks"><?php esc_html_e( 'Number of Banks:', 'rtbcb' ); ?></label>
                            <input type="number" id="roi-num-banks" min="1" max="50" value="8" />
                        </div>

                        <div class="rtbcb-control-group">
                            <label for="roi-monthly-bank-fees"><?php esc_html_e( 'Monthly Bank Fees ($):', 'rtbcb' ); ?></label>
                            <input type="number" id="roi-monthly-bank-fees" min="0" step="100" value="15000" />
                        </div>

                        <div class="rtbcb-control-group">
                            <label for="roi-wire-transfer-volume"><?php esc_html_e( 'Monthly Wire Transfers:', 'rtbcb' ); ?></label>
                            <input type="number" id="roi-wire-transfer-volume" min="0" value="150" />
                        </div>

                        <div class="rtbcb-control-group">
                            <label for="roi-avg-wire-fee"><?php esc_html_e( 'Average Wire Fee ($):', 'rtbcb' ); ?></label>
                            <input type="number" id="roi-avg-wire-fee" min="0" step="1" value="25" />
                        </div>
                    </div>

                    <!-- Risk & Efficiency -->
                    <div class="rtbcb-input-section">
                        <h4><?php esc_html_e( 'Risk & Efficiency Factors', 'rtbcb' ); ?></h4>

                        <div class="rtbcb-control-group">
                            <label for="roi-error-frequency"><?php esc_html_e( 'Weekly Error Incidents:', 'rtbcb' ); ?></label>
                            <input type="number" id="roi-error-frequency" min="0" max="50" value="3" />
                        </div>

                        <div class="rtbcb-control-group">
                            <label for="roi-avg-error-cost"><?php esc_html_e( 'Average Error Cost ($):', 'rtbcb' ); ?></label>
                            <input type="number" id="roi-avg-error-cost" min="0" step="100" value="2500" />
                        </div>

                        <div class="rtbcb-control-group">
                            <label for="roi-compliance-hours"><?php esc_html_e( 'Monthly Compliance Hours:', 'rtbcb' ); ?></label>
                            <input type="number" id="roi-compliance-hours" min="0" step="1" value="40" />
                        </div>

                        <div class="rtbcb-control-group">
                            <label for="roi-system-integration"><?php esc_html_e( 'System Integration Level:', 'rtbcb' ); ?></label>
                            <select id="roi-system-integration">
                                <option value="manual"><?php esc_html_e( 'Mostly Manual', 'rtbcb' ); ?></option>
                                <option value="partial" selected><?php esc_html_e( 'Partially Integrated', 'rtbcb' ); ?></option>
                                <option value="integrated"><?php esc_html_e( 'Well Integrated', 'rtbcb' ); ?></option>
                                <option value="automated"><?php esc_html_e( 'Highly Automated', 'rtbcb' ); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="rtbcb-action-buttons">
                    <button type="button" id="calculate-roi" class="button button-primary">
                        <span class="dashicons dashicons-calculator"></span>
                        <?php esc_html_e( 'Calculate ROI', 'rtbcb' ); ?>
                    </button>
                    <button type="button" id="run-sensitivity-analysis" class="button">
                        <span class="dashicons dashicons-chart-line"></span>
                        <?php esc_html_e( 'Sensitivity Analysis', 'rtbcb' ); ?>
                    </button>
                    <button type="button" id="compare-scenarios" class="button">
                        <span class="dashicons dashicons-slides"></span>
                        <?php esc_html_e( 'Compare Scenarios', 'rtbcb' ); ?>
                    </button>
                    <button type="button" id="export-roi-results" class="button" disabled>
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e( 'Export Results', 'rtbcb' ); ?>
                    </button>
                </div>
            </div>

            <!-- ROI Results Container -->
            <div id="roi-results-container" class="rtbcb-results-container" style="display: none;">
                <div class="rtbcb-results-header">
                    <h3><?php esc_html_e( 'ROI Calculation Results', 'rtbcb' ); ?></h3>
                    <div class="rtbcb-results-actions">
                        <button type="button" id="toggle-roi-details" class="button button-small">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php esc_html_e( 'Show Details', 'rtbcb' ); ?>
                        </button>
                        <button type="button" id="copy-roi-summary" class="button button-small">
                            <span class="dashicons dashicons-admin-page"></span>
                            <?php esc_html_e( 'Copy Summary', 'rtbcb' ); ?>
                        </button>
                    </div>
                </div>

                <!-- ROI Summary Cards -->
                <div class="rtbcb-roi-summary-grid">
                    <div class="rtbcb-roi-card rtbcb-roi-conservative">
                        <div class="rtbcb-roi-card-header">
                            <h4><?php esc_html_e( 'Conservative', 'rtbcb' ); ?></h4>
                            <span class="rtbcb-roi-confidence">70% <?php esc_html_e( 'Confidence', 'rtbcb' ); ?></span>
                        </div>
                        <div class="rtbcb-roi-value">
                            <span class="rtbcb-roi-percentage" id="roi-conservative-percent">--</span>
                            <span class="rtbcb-roi-amount" id="roi-conservative-amount">$--</span>
                        </div>
                        <div class="rtbcb-roi-payback">
                            <span><?php esc_html_e( 'Payback Period:', 'rtbcb' ); ?></span>
                            <strong id="roi-conservative-payback">-- <?php esc_html_e( 'months', 'rtbcb' ); ?></strong>
                        </div>
                    </div>

                    <div class="rtbcb-roi-card rtbcb-roi-realistic">
                        <div class="rtbcb-roi-card-header">
                            <h4><?php esc_html_e( 'Realistic', 'rtbcb' ); ?></h4>
                            <span class="rtbcb-roi-confidence">85% <?php esc_html_e( 'Confidence', 'rtbcb' ); ?></span>
                        </div>
                        <div class="rtbcb-roi-value">
                            <span class="rtbcb-roi-percentage" id="roi-realistic-percent">--</span>
                            <span class="rtbcb-roi-amount" id="roi-realistic-amount">$--</span>
                        </div>
                        <div class="rtbcb-roi-payback">
                            <span><?php esc_html_e( 'Payback Period:', 'rtbcb' ); ?></span>
                            <strong id="roi-realistic-payback">-- <?php esc_html_e( 'months', 'rtbcb' ); ?></strong>
                        </div>
                    </div>

                    <div class="rtbcb-roi-card rtbcb-roi-optimistic">
                        <div class="rtbcb-roi-card-header">
                            <h4><?php esc_html_e( 'Optimistic', 'rtbcb' ); ?></h4>
                            <span class="rtbcb-roi-confidence">95% <?php esc_html_e( 'Confidence', 'rtbcb' ); ?></span>
                        </div>
                        <div class="rtbcb-roi-value">
                            <span class="rtbcb-roi-percentage" id="roi-optimistic-percent">--</span>
                            <span class="rtbcb-roi-amount" id="roi-optimistic-amount">$--</span>
                        </div>
                        <div class="rtbcb-roi-payback">
                            <span><?php esc_html_e( 'Payback Period:', 'rtbcb' ); ?></span>
                            <strong id="roi-optimistic-payback">-- <?php esc_html_e( 'months', 'rtbcb' ); ?></strong>
                        </div>
                    </div>
                </div>

                <!-- ROI Charts -->
                <div class="rtbcb-roi-charts">
                    <div class="rtbcb-chart-container">
                        <h4><?php esc_html_e( 'ROI Comparison Chart', 'rtbcb' ); ?></h4>
                        <canvas id="roi-comparison-chart" width="400" height="200"></canvas>
                    </div>

                    <div class="rtbcb-chart-container">
                        <h4><?php esc_html_e( 'Cost-Benefit Breakdown', 'rtbcb' ); ?></h4>
                        <canvas id="roi-breakdown-chart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Detailed ROI Breakdown -->
                <div id="roi-detailed-breakdown" class="rtbcb-roi-breakdown" style="display: none;">
                    <h4><?php esc_html_e( 'Detailed Cost-Benefit Analysis', 'rtbcb' ); ?></h4>

                    <div class="rtbcb-breakdown-grid">
                        <!-- Benefits Breakdown -->
                        <div class="rtbcb-breakdown-section">
                            <h5><?php esc_html_e( 'Annual Benefits', 'rtbcb' ); ?></h5>
                            <div class="rtbcb-breakdown-items" id="roi-benefits-breakdown">
                                <!-- Populated via JavaScript -->
                            </div>
                        </div>

                        <!-- Costs Breakdown -->
                        <div class="rtbcb-breakdown-section">
                            <h5><?php esc_html_e( 'Annual Costs', 'rtbcb' ); ?></h5>
                            <div class="rtbcb-breakdown-items" id="roi-costs-breakdown">
                                <!-- Populated via JavaScript -->
                            </div>
                        </div>

                        <!-- Assumptions -->
                        <div class="rtbcb-breakdown-section rtbcb-breakdown-full">
                            <h5><?php esc_html_e( 'Key Assumptions', 'rtbcb' ); ?></h5>
                            <div class="rtbcb-assumptions-list" id="roi-assumptions-list">
                                <!-- Populated via JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sensitivity Analysis Container -->
            <div id="sensitivity-analysis-container" class="rtbcb-results-container" style="display: none;">
                <div class="rtbcb-results-header">
                    <h3><?php esc_html_e( 'Sensitivity Analysis', 'rtbcb' ); ?></h3>
                    <p><?php esc_html_e( 'How sensitive is the ROI to changes in key variables?', 'rtbcb' ); ?></p>
                </div>

                <div class="rtbcb-sensitivity-charts">
                    <div class="rtbcb-chart-container rtbcb-chart-full">
                        <h4><?php esc_html_e( 'Sensitivity to Key Variables', 'rtbcb' ); ?></h4>
                        <canvas id="sensitivity-analysis-chart" width="800" height="400"></canvas>
                    </div>
                </div>

                <div class="rtbcb-sensitivity-table">
                    <h4><?php esc_html_e( 'Variable Impact Analysis', 'rtbcb' ); ?></h4>
                    <table class="widefat striped" id="sensitivity-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Variable', 'rtbcb' ); ?></th>
                                <th><?php esc_html_e( 'Base Value', 'rtbcb' ); ?></th>
                                <th><?php esc_html_e( '-20%', 'rtbcb' ); ?></th>
                                <th><?php esc_html_e( '-10%', 'rtbcb' ); ?></th>
                                <th><?php esc_html_e( 'Base ROI', 'rtbcb' ); ?></th>
                                <th><?php esc_html_e( '+10%', 'rtbcb' ); ?></th>
                                <th><?php esc_html_e( '+20%', 'rtbcb' ); ?></th>
                                <th><?php esc_html_e( 'Sensitivity', 'rtbcb' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="sensitivity-table-body">
                            <!-- Populated via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Scenario Comparison Container -->
            <div id="scenario-comparison-container" class="rtbcb-results-container" style="display: none;">
                <div class="rtbcb-results-header">
                    <h3><?php esc_html_e( 'Scenario Comparison', 'rtbcb' ); ?></h3>
                    <p><?php esc_html_e( 'Compare ROI across different company profiles and scenarios', 'rtbcb' ); ?></p>
                </div>

                <div class="rtbcb-scenario-comparison-grid">
                    <div class="rtbcb-chart-container">
                        <h4><?php esc_html_e( 'ROI by Company Size', 'rtbcb' ); ?></h4>
                        <canvas id="scenario-size-chart" width="400" height="300"></canvas>
                    </div>

                    <div class="rtbcb-chart-container">
                        <h4><?php esc_html_e( 'Payback Period Comparison', 'rtbcb' ); ?></h4>
                        <canvas id="scenario-payback-chart" width="400" height="300"></canvas>
                    </div>
                </div>

                <div class="rtbcb-scenario-summary">
                    <h4><?php esc_html_e( 'Scenario Summary', 'rtbcb' ); ?></h4>
                    <table class="widefat striped" id="scenario-comparison-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Scenario', 'rtbcb' ); ?></th>
                                <th><?php esc_html_e( 'Company Size', 'rtbcb' ); ?></th>
                                <th><?php esc_html_e( 'Annual Revenue', 'rtbcb' ); ?></th>
                                <th><?php esc_html_e( 'ROI %', 'rtbcb' ); ?></th>
                                <th><?php esc_html_e( 'Annual Benefit', 'rtbcb' ); ?></th>
                                <th><?php esc_html_e( 'Payback (Months)', 'rtbcb' ); ?></th>
                                <th><?php esc_html_e( 'Recommendation', 'rtbcb' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="scenario-comparison-table-body">
                            <!-- Populated via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden nonce for ROI AJAX requests -->
    <?php wp_nonce_field( 'rtbcb_roi_calculator_test', 'rtbcb_roi_calculator_nonce' ); ?>

    <?php
    // LLM Tests Section

    // Get available models for testing
    $available_models = [
        'mini'     => get_option( 'rtbcb_mini_model', rtbcb_get_default_model( 'mini' ) ),
        'premium'  => get_option( 'rtbcb_premium_model', rtbcb_get_default_model( 'premium' ) ),
        'advanced' => get_option( 'rtbcb_advanced_model', rtbcb_get_default_model( 'advanced' ) ),
    ];
    ?>

    <!-- LLM Tests Section -->
    <div id="llm-tests" class="rtbcb-test-section" style="display: none;">
        <div class="rtbcb-test-panel">
            <div class="rtbcb-panel-header">
                <h2><?php esc_html_e('LLM Integration Testing', 'rtbcb'); ?></h2>
                <p><?php esc_html_e('Test different models, compare responses, and optimize performance', 'rtbcb'); ?></p>
            </div>

            <!-- Test Configuration -->
            <div class="rtbcb-test-controls">
                <div class="rtbcb-llm-config-grid">
                    <div class="rtbcb-config-section">
                        <h4><?php esc_html_e('Test Configuration', 'rtbcb'); ?></h4>

                        <!-- Model Selection Controls -->
                        <div class="rtbcb-model-matrix">
                            <h4><?php esc_html_e('Model Comparison Matrix', 'rtbcb'); ?></h4>
                            <div class="rtbcb-model-grid">
                                <?php foreach ($available_models as $key => $model): ?>
                                <label class="rtbcb-model-option">
                                    <input type="checkbox" name="llm-models[]" value="<?php echo esc_attr($key); ?>" checked />
                                    <span class="rtbcb-model-info">
                                        <strong><?php echo esc_html(ucfirst($key)); ?></strong>
                                        <small><?php echo esc_html($model); ?></small>
                                        <span class="rtbcb-cost-estimate" data-model="<?php echo esc_attr($key); ?>">~$0.00</span>
                                    </span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Prompt Engineering Section -->
                        <div class="rtbcb-prompt-variants">
                            <h4><?php esc_html_e('A/B Prompt Testing', 'rtbcb'); ?></h4>
                            <div class="rtbcb-variant-container">
                                <div class="rtbcb-variant-item" data-variant="A">
                                    <label><?php esc_html_e('Prompt A:', 'rtbcb'); ?></label>
                                    <textarea id="llm-prompt-a" rows="4" placeholder="<?php esc_attr_e('Enter first prompt variant...', 'rtbcb'); ?>"></textarea>
                                    <div class="rtbcb-variant-controls">
                                        <label><?php esc_html_e('Temperature:', 'rtbcb'); ?> <input type="range" min="0" max="2" step="0.1" value="0.3" /></label>
                                        <span class="temperature-display">0.3</span>
                                    </div>
                                </div>
                                <div class="rtbcb-variant-item" data-variant="B">
                                    <label><?php esc_html_e('Prompt B (Optional):', 'rtbcb'); ?></label>
                                    <textarea id="llm-prompt-b" rows="4" placeholder="<?php esc_attr_e('Enter second prompt variant...', 'rtbcb'); ?>"></textarea>
                                    <div class="rtbcb-variant-controls">
                                        <label><?php esc_html_e('Temperature:', 'rtbcb'); ?> <input type="range" min="0" max="2" step="0.1" value="0.7" /></label>
                                        <span class="temperature-display">0.7</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rtbcb-control-row">
                            <div class="rtbcb-control-group">
                                <label for="llm-max-tokens"><?php esc_html_e('Max Tokens:', 'rtbcb'); ?></label>
                                <input type="number" id="llm-max-tokens" min="100" max="4000" value="1000" />
                            </div>

                            <div class="rtbcb-control-group">
                                <label for="llm-temperature"><?php esc_html_e('Temperature:', 'rtbcb'); ?></label>
                                <input type="range" id="llm-temperature" min="0" max="2" step="0.1" value="0.3" />
                                <span id="llm-temperature-value">0.3</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rtbcb-action-buttons">
                    <button type="button" id="run-llm-matrix-test" class="button button-primary" 
                            data-action="run-llm-test" data-default-text="Run Model Matrix Test">
                        <span class="dashicons dashicons-networking"></span>
                        <?php esc_html_e('Run Model Matrix Test', 'rtbcb'); ?>
                    </button>

                    <button type="button" id="export-llm-results" class="button" disabled
                            data-action="export-results" data-export-type="llm">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Export Results', 'rtbcb'); ?>
                    </button>
                </div>
            </div>

            <!-- Results Display -->
            <div id="llm-test-results" class="rtbcb-results-container" style="display: none;">
                <div class="rtbcb-results-header">
                    <h3><?php esc_html_e('LLM Test Results', 'rtbcb'); ?></h3>
                    <div class="rtbcb-results-meta" id="llm-results-meta"></div>
                </div>

                <!-- Performance Summary -->
                <div class="rtbcb-performance-summary" id="llm-performance-summary">
                    <!-- Populated by JavaScript -->
                </div>

                <!-- Response Comparison Table -->
                <div class="rtbcb-comparison-table-container">
                    <table class="wp-list-table widefat fixed striped" id="llm-comparison-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Model', 'rtbcb'); ?></th>
                                <th><?php esc_html_e('Response Time', 'rtbcb'); ?></th>
                                <th><?php esc_html_e('Tokens Used', 'rtbcb'); ?></th>
                                <th><?php esc_html_e('Cost Est.', 'rtbcb'); ?></th>
                                <th><?php esc_html_e('Quality Score', 'rtbcb'); ?></th>
                                <th><?php esc_html_e('Response Preview', 'rtbcb'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="llm-comparison-tbody">
                            <!-- Populated by JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Performance Chart -->
                <div class="rtbcb-chart-container">
                    <h4><?php esc_html_e('Performance Comparison', 'rtbcb'); ?></h4>
                    <canvas id="llm-performance-chart" width="800" height="400"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- RAG System Test Section -->
    <div id="rag-system" class="rtbcb-test-section" style="display: none;">
        <div class="rtbcb-test-panel">
            <div class="rtbcb-panel-header">
                <h2><?php esc_html_e( 'RAG System Testing', 'rtbcb' ); ?></h2>
                <p><?php esc_html_e( 'Test vector search, retrieval, and context validation', 'rtbcb' ); ?></p>
            </div>

            <div class="rtbcb-index-overview">
                <div class="rtbcb-status-indicator <?php echo esc_attr( $index_size > 0 ? 'status-good' : 'status-warning' ); ?>" id="rtbcb-rag-index-status">
                    <span class="dashicons <?php echo esc_attr( $index_size > 0 ? 'dashicons-yes-alt' : 'dashicons-warning' ); ?>"></span>
                    <span><?php esc_html_e( 'RAG Index', 'rtbcb' ); ?></span>
                </div>
                <div class="rtbcb-index-meta">
                    <span id="rtbcb-rag-last-indexed">
                        <?php
                        echo $last_indexed ? esc_html( sprintf( __( 'Last indexed: %s', 'rtbcb' ), $last_indexed ) ) : esc_html__( 'Index never built.', 'rtbcb' );
                        ?>
                    </span>
                    <span id="rtbcb-rag-index-size"><?php printf( esc_html__( 'Entries: %d', 'rtbcb' ), intval( $index_size ) ); ?></span>
                </div>
                <button type="button" id="rtbcb-rag-rebuild" class="button"><?php esc_html_e( 'Rebuild Index', 'rtbcb' ); ?></button>
                <div id="rtbcb-rag-index-notice" class="rtbcb-index-notice"></div>
            </div>

            <div class="rtbcb-test-controls">
                <div class="rtbcb-control-group">
                    <label for="rtbcb-rag-query"><?php esc_html_e( 'Test Query', 'rtbcb' ); ?></label>
                    <input type="text" id="rtbcb-rag-query" class="regular-text" placeholder="<?php esc_attr_e( 'Enter query...', 'rtbcb' ); ?>" />
                </div>
                <div class="rtbcb-control-group">
                    <label for="rtbcb-rag-top-k"><?php esc_html_e( 'Top K', 'rtbcb' ); ?></label>
                    <input type="number" id="rtbcb-rag-top-k" min="1" max="20" value="3" />
                </div>
                <div class="rtbcb-control-group">
                    <label for="rtbcb-rag-type"><?php esc_html_e( 'Type', 'rtbcb' ); ?></label>
                    <select id="rtbcb-rag-type">
                        <option value="all"><?php esc_html_e( 'All', 'rtbcb' ); ?></option>
                        <option value="vendor"><?php esc_html_e( 'Vendor', 'rtbcb' ); ?></option>
                        <option value="note"><?php esc_html_e( 'Note', 'rtbcb' ); ?></option>
                    </select>
                </div>
                <div class="rtbcb-control-group">
                    <label>
                        <input type="checkbox" id="rtbcb-rag-use-context" />
                        <?php esc_html_e( 'Use retrieved context in LLM tests', 'rtbcb' ); ?>
                    </label>
                </div>
                <div class="rtbcb-action-buttons">
                    <button type="button" id="rtbcb-run-rag-query" class="button button-primary" disabled>
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e( 'Run Retrieval', 'rtbcb' ); ?>
                    </button>
                    <button type="button" id="rtbcb-rag-cancel" class="button" style="display: none;">
                        <?php esc_html_e( 'Cancel', 'rtbcb' ); ?>
                    </button>
                </div>
                <div id="rtbcb-rag-progress" class="rtbcb-progress" style="display: none;"></div>
            </div>

            <div id="rtbcb-rag-results" style="display: none;">
                <div id="rtbcb-rag-metrics" class="rtbcb-rag-metrics"></div>
                <table class="wp-list-table widefat fixed striped" id="rtbcb-rag-results-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Type', 'rtbcb' ); ?></th>
                            <th><?php esc_html_e( 'Ref ID', 'rtbcb' ); ?></th>
                            <th><?php esc_html_e( 'Title/Description', 'rtbcb' ); ?></th>
                            <th><?php esc_html_e( 'Score', 'rtbcb' ); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <div class="rtbcb-action-buttons">
                    <button type="button" id="rtbcb-copy-rag-context" class="button" disabled><?php esc_html_e( 'Copy Context', 'rtbcb' ); ?></button>
                    <button type="button" id="rtbcb-export-rag-results" class="button" disabled><?php esc_html_e( 'Export Results', 'rtbcb' ); ?></button>
                </div>
                <details id="rtbcb-rag-debug" class="rtbcb-debug-panel">
                    <summary><?php esc_html_e( 'Debug Info', 'rtbcb' ); ?></summary>
                    <pre></pre>
                </details>
            </div>
        </div>
    </div>

    <!-- API Health Test Section -->
    <?php
    $api_components = [
        'chat'      => __( 'OpenAI Chat API', 'rtbcb' ),
        'embedding' => __( 'OpenAI Embedding API', 'rtbcb' ),
        'portal'    => __( 'Real Treasury Portal', 'rtbcb' ),
        'roi'       => __( 'ROI Calculator', 'rtbcb' ),
        'rag'       => __( 'RAG Index', 'rtbcb' ),
    ];
    $descriptions   = [
        'chat'      => __( 'Tests connectivity to OpenAI chat completion endpoint.', 'rtbcb' ),
        'embedding' => __( 'Verifies embedding generation is available.', 'rtbcb' ),
        'portal'    => __( 'Checks that vendor data can be retrieved from the portal.', 'rtbcb' ),
        'roi'       => __( 'Runs a sample ROI calculation.', 'rtbcb' ),
        'rag'       => __( 'Ensures the RAG index responds to search queries.', 'rtbcb' ),
    ];
    $last_api_test  = get_option( 'rtbcb_last_api_test', [] );
    $last_results   = $last_api_test['results'] ?? [];
    $last_timestamp = $last_api_test['timestamp'] ?? '';
    ?>
    <div id="api-health" class="rtbcb-test-section" style="display: none;">
        <div class="rtbcb-test-panel">
            <div class="rtbcb-panel-header">
                <h2><?php esc_html_e( 'API Health Testing', 'rtbcb' ); ?></h2>
                <p><?php esc_html_e( 'Monitor API connectivity, rate limits, and error handling', 'rtbcb' ); ?></p>
            </div>
            <div class="rtbcb-api-controls">
                <button type="button" id="rtbcb-run-all-api-tests" class="button button-primary">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e( 'Run All Tests', 'rtbcb' ); ?>
                </button>
                <div id="rtbcb-api-health-notice" class="rtbcb-api-health-notice">
                    <?php
                    if ( $last_timestamp ) {
                        printf( esc_html__( 'Last tested: %s', 'rtbcb' ), esc_html( $last_timestamp ) );
                    } else {
                        esc_html_e( 'No tests run yet.', 'rtbcb' );
                    }
                    ?>
                </div>
            </div>

            <table class="rtbcb-api-health-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Status', 'rtbcb' ); ?></th>
                        <th><?php esc_html_e( 'Component', 'rtbcb' ); ?></th>
                        <th><?php esc_html_e( 'Last Tested', 'rtbcb' ); ?></th>
                        <th><?php esc_html_e( 'Response Time', 'rtbcb' ); ?></th>
                        <th><?php esc_html_e( 'Message', 'rtbcb' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'rtbcb' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $api_components as $key => $label ) :
                        $result       = $last_results[ $key ] ?? [];
                        $passed       = ! empty( $result['passed'] );
                        $status_class = $passed ? 'status-good' : ( $result ? 'status-error' : '' );
                        $icon         = $result ? ( $passed ? 'dashicons-yes-alt' : 'dashicons-warning' ) : 'dashicons-minus';
                        ?>
                        <tr id="rtbcb-api-<?php echo esc_attr( $key ); ?>" data-component="<?php echo esc_attr( $key ); ?>">
                            <td class="rtbcb-status">
                                <span class="rtbcb-status-indicator <?php echo esc_attr( $status_class ); ?>">
                                    <span class="dashicons <?php echo esc_attr( $icon ); ?>"></span>
                                </span>
                            </td>
                            <td class="rtbcb-component-name">
                                <?php echo esc_html( $label ); ?>
                                <span class="dashicons dashicons-info" title="<?php echo esc_attr( $descriptions[ $key ] ); ?>"></span>
                            </td>
                            <td class="rtbcb-last-tested">
                                <?php echo isset( $result['last_tested'] ) ? esc_html( $result['last_tested'] ) : '&#8212;'; ?>
                            </td>
                            <td class="rtbcb-response-time">
                                <?php echo isset( $result['response_time'] ) ? intval( $result['response_time'] ) . ' ms' : '&#8212;'; ?>
                            </td>
                            <td class="rtbcb-message">
                                <?php echo isset( $result['message'] ) ? esc_html( $result['message'] ) : esc_html__( 'Not tested', 'rtbcb' ); ?>
                            </td>
                            <td class="rtbcb-actions">
                                <button type="button" class="button rtbcb-retest" data-component="<?php echo esc_attr( $key ); ?>">
                                    <?php esc_html_e( 'Retest', 'rtbcb' ); ?>
                                </button>
                                <button type="button" class="button rtbcb-view-details" data-component="<?php echo esc_attr( $key ); ?>">
                                    <?php esc_html_e( 'Details', 'rtbcb' ); ?>
                                </button>
                            </td>
                        </tr>
                        <tr class="rtbcb-test-details" id="rtbcb-details-<?php echo esc_attr( $key ); ?>" style="display: none;">
                            <td colspan="6"><pre></pre></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Data Health Section -->
    <div id="data-health" class="rtbcb-test-section" style="display: none;">
        <div class="rtbcb-test-panel">
            <div class="rtbcb-panel-header">
                <h2><?php esc_html_e( 'Data Health', 'rtbcb' ); ?></h2>
                <p><?php esc_html_e( 'Verify database, API, and file system health.', 'rtbcb' ); ?></p>
            </div>
            <div class="rtbcb-data-health-summary">
                <p>
                    <strong><?php esc_html_e( 'Portal Integration:', 'rtbcb' ); ?></strong>
                    <?php echo $portal_active ? esc_html__( 'Active', 'rtbcb' ) : esc_html__( 'Inactive', 'rtbcb' ); ?>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Last RAG Index:', 'rtbcb' ); ?></strong>
                    <?php echo esc_html( $last_index_display ); ?>
                </p>
            </div>
            <div class="rtbcb-data-health-controls">
                <button type="button" id="rtbcb-run-data-health" class="button button-primary">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e( 'Run Checks', 'rtbcb' ); ?>
                </button>
            </div>
            <table class="rtbcb-data-health-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Status', 'rtbcb' ); ?></th>
                        <th><?php esc_html_e( 'Check', 'rtbcb' ); ?></th>
                        <th><?php esc_html_e( 'Message', 'rtbcb' ); ?></th>
                    </tr>
                </thead>
                <tbody id="rtbcb-data-health-results">
                    <tr>
                        <td colspan="3"><?php esc_html_e( 'No checks run yet.', 'rtbcb' ); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Report Preview Section -->
    <div id="report-preview" class="rtbcb-test-section" style="display: none;">
        <div class="rtbcb-test-panel">
            <div class="rtbcb-panel-header">
                <h2><?php esc_html_e( 'Report Preview', 'rtbcb' ); ?></h2>
                <p><?php esc_html_e( 'Generate a preview of the full report.', 'rtbcb' ); ?></p>
            </div>
            <div class="rtbcb-report-controls">
                <button type="button" id="rtbcb-generate-preview-report" class="button button-primary">
                    <span class="dashicons dashicons-media-document"></span>
                    <?php esc_html_e( 'Generate Report', 'rtbcb' ); ?>
                </button>
            </div>
            <div id="rtbcb-report-preview-container">
                <iframe id="rtbcb-report-preview-frame"></iframe>
            </div>
        </div>
    </div>

    <!-- Settings Section -->
    <div id="settings" class="rtbcb-test-section" style="display: none;">
        <div class="rtbcb-test-panel">
            <div class="rtbcb-panel-header">
                <h2><?php esc_html_e( 'Settings', 'rtbcb' ); ?></h2>
                <p><?php esc_html_e( 'Configure plugin options.', 'rtbcb' ); ?></p>
            </div>
            <form id="rtbcb-dashboard-settings-form" method="post">
                <?php wp_nonce_field( 'rtbcb_save_dashboard_settings', 'nonce' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="rtbcb_openai_api_key"><?php esc_html_e( 'OpenAI API Key', 'rtbcb' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="rtbcb_openai_api_key" name="rtbcb_openai_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e( 'Diagnostics', 'rtbcb' ); ?></label>
                        </th>
                        <td>
                            <button type="button" class="button" id="rtbcb-run-tests" data-nonce="<?php echo esc_attr( wp_create_nonce( 'rtbcb_nonce' ) ); ?>"><?php esc_html_e( 'Run Diagnostics', 'rtbcb' ); ?></button>
                            <p class="description"><?php esc_html_e( 'Verify integration and system health.', 'rtbcb' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="rtbcb_mini_model"><?php esc_html_e( 'Mini Model', 'rtbcb' ); ?></label>
                        </th>
                        <td>
                            <select id="rtbcb_mini_model" name="rtbcb_mini_model">
                                <?php foreach ( $chat_models as $value => $label ) : ?>
                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $mini_model, $value ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="rtbcb_premium_model"><?php esc_html_e( 'Premium Model', 'rtbcb' ); ?></label>
                        </th>
                        <td>
                            <select id="rtbcb_premium_model" name="rtbcb_premium_model">
                                <?php foreach ( $chat_models as $value => $label ) : ?>
                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $premium_model, $value ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="rtbcb_advanced_model"><?php esc_html_e( 'Advanced Model', 'rtbcb' ); ?></label>
                        </th>
                        <td>
                            <select id="rtbcb_advanced_model" name="rtbcb_advanced_model">
                                <?php foreach ( $chat_models as $value => $label ) : ?>
                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $advanced_model, $value ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="rtbcb_embedding_model"><?php esc_html_e( 'Embedding Model', 'rtbcb' ); ?></label>
                        </th>
                        <td>
                            <select id="rtbcb_embedding_model" name="rtbcb_embedding_model">
                                <?php foreach ( $embedding_models as $value => $label ) : ?>
                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $embedding_model, $value ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'rtbcb' ); ?></button>
                </p>
            </form>
        </div>
    </div>
</div>

