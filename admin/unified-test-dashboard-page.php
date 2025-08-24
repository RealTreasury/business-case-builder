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
