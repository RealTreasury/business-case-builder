<?php
/**
 * Full report test page with regeneration and export options.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Report Test', 'rtbcb' ); ?></h1>
    <?php wp_nonce_field( 'rtbcb_test_generate_complete_report', 'rtbcb_test_generate_complete_report_nonce' ); ?>
    <p>
        <button type="button" id="rtbcb-generate-complete-report" class="button button-primary">
            <?php esc_html_e( 'Generate Complete Report', 'rtbcb' ); ?>
        </button>
        <button type="button" id="rtbcb-export-report-html" class="button">
            <?php esc_html_e( 'Export HTML', 'rtbcb' ); ?>
        </button>
        <button type="button" id="rtbcb-export-report-pdf" class="button">
            <?php esc_html_e( 'Export PDF', 'rtbcb' ); ?>
        </button>
    </p>
    <div id="rtbcb-report-meta"></div>
    <div id="rtbcb-report-preview"></div>
</div>

<script>
// Ensure ajaxurl is available
<?php if ( ! isset( $GLOBALS['ajaxurl'] ) || empty( $GLOBALS['ajaxurl'] ) ) : ?>
var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
<?php endif; ?>
</script>

