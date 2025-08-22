<?php
/**
 * Report preview admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$sample_context = [
    'company_name'  => 'Sample Company',
    'analysis_date' => current_time( 'Y-m-d' ),
];
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Report Preview', 'rtbcb' ); ?></h1>
    <form id="rtbcb-report-preview-form">
        <p>
            <label for="rtbcb-sample-context">
                <?php esc_html_e( 'Business Context (JSON)', 'rtbcb' ); ?>
            </label>
            <textarea id="rtbcb-sample-context" name="context" rows="8" class="large-text"><?php echo esc_textarea( wp_json_encode( $sample_context, JSON_PRETTY_PRINT ) ); ?></textarea>
        </p>
        <p>
            <label for="rtbcb-template-override">
                <?php esc_html_e( 'Template Override (optional PHP/HTML)', 'rtbcb' ); ?>
            </label>
            <textarea id="rtbcb-template-override" name="template" rows="8" class="large-text"></textarea>
        </p>
        <?php wp_nonce_field( 'rtbcb_generate_report_preview', 'nonce' ); ?>
        <p>
            <button type="submit" class="button button-primary" id="rtbcb-generate-report">
                <?php esc_html_e( 'Generate Report', 'rtbcb' ); ?>
            </button>
            <button type="button" class="button" id="rtbcb-download-pdf" style="display:none;">
                <?php esc_html_e( 'Download PDF', 'rtbcb' ); ?>
            </button>
        </p>
    </form>
    <div id="rtbcb-report-preview">
        <iframe id="rtbcb-report-iframe"></iframe>
    </div>
</div>

