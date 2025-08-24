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
</div>
