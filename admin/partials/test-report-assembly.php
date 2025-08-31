<?php
/**
 * Report assembly and delivery test section.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

defined( 'ABSPATH' ) || exit;

$allowed = rtbcb_require_completed_steps( 'rtbcb-test-report-assembly', false );
if ( ! $allowed ) {
    echo '<div class="notice notice-warning inline"><p>' .
        esc_html__( 'Please complete previous steps before accessing this section.', 'rtbcb' ) .
        '</p></div>';
    return;
}

$summary = get_option( 'rtbcb_executive_summary', [] );
?>
<h2><?php esc_html_e( 'Report Assembly & Delivery', 'rtbcb' ); ?></h2>
<p class="description">
    <?php esc_html_e( 'Generate an executive summary to verify report assembly.', 'rtbcb' ); ?>
</p>
<p class="rtbcb-data-source">
    <span class="rtbcb-data-status rtbcb-status-executive-summary">⚪ <?php esc_html_e( 'Generate new', 'rtbcb' ); ?></span>
    <a href="#rtbcb-comprehensive-analysis" class="rtbcb-view-source" style="display:none;">
        <?php esc_html_e( 'View Source Data', 'rtbcb' ); ?>
    </a>
</p>
<div class="card">
    <h3 class="title"><?php esc_html_e( 'Executive Summary', 'rtbcb' ); ?></h3>
    <form id="rtbcb-report-assembly-form">
        <?php wp_nonce_field( 'rtbcb_test_report_assembly', 'nonce' ); ?>
        <p class="submit">
            <button type="submit" class="button button-primary">
                <?php esc_html_e( 'Generate Summary', 'rtbcb' ); ?>
            </button>
            <button type="button" id="rtbcb-clear-report-summary" class="button">
                <?php esc_html_e( 'Clear', 'rtbcb' ); ?>
            </button>
        </p>
    </form>
    <div id="rtbcb-report-assembly-results">
        <?php if ( ! empty( $summary ) ) : ?>
            <pre><?php echo esc_html( wp_json_encode( $summary, JSON_PRETTY_PRINT ) ); ?></pre>
        <?php endif; ?>
    </div>
</div>
<script>
document.getElementById('rtbcb-clear-report-summary')?.addEventListener('click', function() {
    document.getElementById('rtbcb-report-assembly-results').innerHTML = '';
});
</script>
