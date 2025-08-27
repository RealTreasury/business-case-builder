<?php
/**
 * Partial for Report Preview section.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$rtbcb_sample_forms = rtbcb_get_sample_report_forms();
$first_scenario     = reset( $rtbcb_sample_forms );
$sample_context     = $first_scenario['data'];

$company = rtbcb_get_current_company();
if ( empty( $company ) ) {
    $overview_url = admin_url( 'admin.php?page=rtbcb-test-dashboard#rtbcb-test-company-overview' );
    echo '<div class="notice notice-error"><p>' . sprintf(
        esc_html__( 'No company data found. Please run the %s first.', 'rtbcb' ),
        '<a href="' . esc_url( $overview_url ) . '">' . esc_html__( 'Company Overview', 'rtbcb' ) . '</a>'
    ) . '</p></div>';
    return;
}
?>
<h2><?php esc_html_e( 'Report Preview', 'rtbcb' ); ?></h2>
<p class="description"><?php esc_html_e( 'Preview the generated business case report before finalizing.', 'rtbcb' ); ?></p>
<?php $rtbcb_last = rtbcb_get_last_test_result( 'rtbcb-report-preview', $test_results ?? [] ); ?>
<?php if ( $rtbcb_last ) : ?>
    <div class="notice notice-info" role="status">
        <p><strong><?php esc_html_e( 'Status:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['status'] ); ?></p>
        <p><strong><?php esc_html_e( 'Message:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['message'] ); ?></p>
        <p><strong><?php esc_html_e( 'Timestamp:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['timestamp'] ); ?></p>
        <p class="submit">
            <button type="button" class="button" id="rtbcb-rerun-report-preview" data-section="rtbcb-report-preview">
                <?php esc_html_e( 'Regenerate', 'rtbcb' ); ?>
            </button>
        </p>
    </div>
<?php endif; ?>
<form id="rtbcb-report-preview-form" method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
    <input type="hidden" name="action" value="rtbcb_generate_report_preview" />
    <p>
        <label for="rtbcb-sample-select"><?php esc_html_e( 'Sample Scenarios', 'rtbcb' ); ?></label>
        <select id="rtbcb-sample-select" class="regular-text">
            <option value=""></option>
            <?php foreach ( $rtbcb_sample_forms as $key => $scenario ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $scenario['label'] ); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="button" id="rtbcb-load-sample"><?php esc_html_e( 'Load Sample', 'rtbcb' ); ?></button>
    </p>
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
<div id="rtbcb-report-preview-card" class="rtbcb-result-card" style="display:none;">
    <details>
        <summary><?php esc_html_e( 'Preview', 'rtbcb' ); ?></summary>
        <div id="rtbcb-report-preview">
            <iframe id="rtbcb-report-iframe"></iframe>
        </div>
    </details>
</div>
<script>
document.getElementById( 'rtbcb-rerun-report-preview' )?.addEventListener( 'click', function() {
    document.getElementById( 'rtbcb-generate-report' ).click();
});
</script>
