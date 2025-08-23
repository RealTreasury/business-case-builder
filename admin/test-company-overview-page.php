<?php
/**
 * Test Company Overview admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Test Company Overview', 'rtbcb' ); ?></h1>
    <p>
        <label for="rtbcb-test-company-name">
            <?php esc_html_e( 'Company Name', 'rtbcb' ); ?>
        </label>
        <input type="text" id="rtbcb-test-company-name" />
        <?php wp_nonce_field( 'rtbcb_test_company_overview', 'rtbcb_test_company_overview_nonce' ); ?>
        <button type="button" id="rtbcb-generate-company-overview" class="button button-primary">
            <?php esc_html_e( 'Generate Overview', 'rtbcb' ); ?>
        </button>
        <button type="button" id="rtbcb-clear-company-overview" class="button">
            <?php esc_html_e( 'Clear Results', 'rtbcb' ); ?>
        </button>
    </p>
    <div id="rtbcb-company-overview-results"></div>
</div>

