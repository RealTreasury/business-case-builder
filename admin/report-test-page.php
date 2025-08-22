<?php
/**
 * Sample report test page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Report Test', 'rtbcb' ); ?></h1>
    <p>
        <?php wp_nonce_field( 'rtbcb_generate_report_preview', 'nonce' ); ?>
        <button type="button" id="rtbcb-generate-sample-report" class="button button-primary">
            <?php esc_html_e( 'Generate Sample Report', 'rtbcb' ); ?>
        </button>
    </p>
    <div id="rtbcb-sample-report-container">
        <iframe id="rtbcb-sample-report-frame"></iframe>
    </div>
</div>
