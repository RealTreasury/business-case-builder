<?php
/**
 * Unified test dashboard admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Unified Test Dashboard', 'rtbcb' ); ?></h1>
    <h2 class="nav-tab-wrapper">
        <a href="#company-overview" class="nav-tab nav-tab-active" data-tab="company-overview">
            <span class="dashicons dashicons-building"></span>
            <?php esc_html_e( 'Company Overview', 'rtbcb' ); ?>
        </a>
        <a href="#roi-calculator" class="nav-tab" data-tab="roi-calculator">
            <span class="dashicons dashicons-chart-pie"></span>
            <?php esc_html_e( 'ROI Calculator', 'rtbcb' ); ?>
        </a>
    </h2>

    <div id="company-overview" class="rtbcb-test-section">
        <div class="rtbcb-test-panel">
            <p>
                <label for="rtbcb-company-name"><?php esc_html_e( 'Company Name', 'rtbcb' ); ?></label>
                <input type="text" id="rtbcb-company-name" class="regular-text" />
            </p>
            <p>
                <label for="rtbcb-model"><?php esc_html_e( 'Model', 'rtbcb' ); ?></label>
                <select id="rtbcb-model">
                    <option value="mini"><?php echo esc_html( get_option( 'rtbcb_mini_model', 'gpt-4o-mini' ) ); ?></option>
                    <option value="premium"><?php echo esc_html( get_option( 'rtbcb_premium_model', 'gpt-4o' ) ); ?></option>
                    <option value="advanced"><?php echo esc_html( get_option( 'rtbcb_advanced_model', 'o1-preview' ) ); ?></option>
                </select>
            </p>
            <p>
                <label>
                    <input type="checkbox" id="rtbcb-debug" /> <?php esc_html_e( 'Include debug information', 'rtbcb' ); ?>
                </label>
            </p>
            <?php wp_nonce_field( 'rtbcb_unified_test_dashboard', 'rtbcb_unified_test_dashboard_nonce' ); ?>
            <p>
                <button type="button" class="button button-primary rtbcb-generate-overview">
                    <?php esc_html_e( 'Generate Overview', 'rtbcb' ); ?>
                </button>
                <button type="button" id="rtbcb-clear-results" class="button">
                    <?php esc_html_e( 'Clear Results', 'rtbcb' ); ?>
                </button>
            </p>
            <p id="rtbcb-overview-status"></p>
            <pre id="rtbcb-overview-output"></pre>
        </div>
    </div>
    <?php
    /**
     * ROI Calculator Testing Module - Add to unified-test-dashboard-page.php
     * Replace the ROI Calculator placeholder section with this comprehensive implementation
     */
    ?>

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
</div>
