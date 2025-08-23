<?php
/**
 * Report preview admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_company    = rtbcb_get_current_company();
$clear_nonce        = wp_create_nonce( 'rtbcb_clear_current_company' );
$rtbcb_sample_forms = rtbcb_get_sample_report_forms();
$first_scenario     = reset( $rtbcb_sample_forms );
$sample_context     = $first_scenario['data'];
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Report Preview', 'rtbcb' ); ?></h1>
    <p>
        <button type="button" id="rtbcb-start-new-analysis" class="button">
            <?php esc_html_e( 'Start New Company Analysis', 'rtbcb' ); ?>
        </button>
    </p>

<?php if ( empty( $current_company ) ) : ?>
    <div class="notice notice-error">
        <p><?php esc_html_e( 'No company selected. Please run the company overview.', 'rtbcb' ); ?></p>
    </div>
<?php else : ?>
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
    <div id="rtbcb-report-preview">
        <iframe id="rtbcb-report-iframe"></iframe>
    </div>
<?php endif; ?>
</div>

<script>
// Ensure ajaxurl is available
<?php if ( ! isset( $GLOBALS['ajaxurl'] ) || empty( $GLOBALS['ajaxurl'] ) ) : ?>
var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
<?php endif; ?>
document.getElementById('rtbcb-start-new-analysis').addEventListener('click', function () {
    var data = new FormData();
    data.append('action', 'rtbcb_clear_current_company');
    data.append('nonce', '<?php echo esc_js( $clear_nonce ); ?>');
    fetch(ajaxurl, { method: 'POST', body: data })
        .then(function (response) { return response.json(); })
        .then(function () { location.reload(); });
});
</script>

