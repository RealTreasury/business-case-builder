<?php
/**
 * Partial for Test Value Proposition section.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! rtbcb_require_completed_steps( 'rtbcb-test-value-proposition', false ) ) {
    echo '<div class="notice notice-warning inline"><p>' .
        esc_html__( 'Please complete previous steps before accessing this section.', 'rtbcb' ) .
        '</p></div>';
    return;
}

?>
<h2><?php esc_html_e( 'Test Value Proposition', 'rtbcb' ); ?></h2>
<p class="description"><?php esc_html_e( 'Generate a personalized opening paragraph for the report.', 'rtbcb' ); ?></p>
<p class="rtbcb-data-source">
    <span class="rtbcb-data-status rtbcb-status-value-proposition">⚪ <?php esc_html_e( 'Generate new', 'rtbcb' ); ?></span>
    <a href="#rtbcb-comprehensive-analysis" class="rtbcb-view-source" style="display:none;">
        <?php esc_html_e( 'View Source Data', 'rtbcb' ); ?>
    </a>
</p>
<?php $rtbcb_last = rtbcb_get_last_test_result( 'rtbcb-test-value-proposition', $test_results ?? [] ); ?>
<?php if ( $rtbcb_last ) : ?>
    <div class="notice notice-info" role="status">
        <p><strong><?php esc_html_e( 'Status:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['status'] ); ?></p>
        <p><strong><?php esc_html_e( 'Message:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['message'] ); ?></p>
        <p><strong><?php esc_html_e( 'Timestamp:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['timestamp'] ); ?></p>
        <p class="submit">
            <button type="button" class="button" id="rtbcb-rerun-value-proposition" data-section="rtbcb-test-value-proposition">
                <?php esc_html_e( 'Regenerate This Section Only', 'rtbcb' ); ?>
            </button>
        </p>
    </div>
<?php endif; ?>
<div class="card">
    <h3 class="title"><?php esc_html_e( 'Generate Value Proposition', 'rtbcb' ); ?></h3>
    <?php wp_nonce_field( 'rtbcb_test_value_proposition', 'rtbcb_test_value_proposition_nonce' ); ?>
    <p class="submit">
        <button type="button" id="rtbcb-generate-value-proposition" class="button button-primary">
            <?php esc_html_e( 'Generate', 'rtbcb' ); ?>
        </button>
    </p>
</div>
<div id="rtbcb-value-proposition-card" class="rtbcb-result-card" style="display:none;">
    <details>
        <summary><?php esc_html_e( 'Opening Paragraph', 'rtbcb' ); ?></summary>
        <div id="rtbcb-value-proposition-results"></div>
    </details>
</div>
<script>
    jQuery('#rtbcb-generate-value-proposition').on('click', function(){
        const btn = jQuery(this);
        const original = rtbcbTestUtils.showLoading(btn);
        jQuery.post(ajaxurl, {
            action: 'rtbcb_test_value_proposition',
            nonce: jQuery('#rtbcb_test_value_proposition_nonce').val()
        }).done(function(response){
            if (response.success) {
                const data = response.data;
                rtbcbTestUtils.renderSuccess(jQuery('#rtbcb-value-proposition-results'), data.paragraph, null, data);
            jQuery('#rtbcb-value-proposition-card').show();
        } else {
            rtbcbTestUtils.renderError(jQuery('#rtbcb-value-proposition-results'), response.data.message);
        }
    }).always(function(){
        rtbcbTestUtils.hideLoading(btn, original);
    });
 });
 document.getElementById('rtbcb-rerun-value-proposition')?.addEventListener('click', function(){
    document.getElementById('rtbcb-generate-value-proposition').click();
 });
</script>
