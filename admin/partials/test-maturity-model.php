<?php
/**
 * Partial for Test Maturity Model section.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! rtbcb_require_completed_steps( 'rtbcb-test-maturity-model' ) ) {
    return;
}

?>
<h2><?php esc_html_e( 'Test Maturity Model', 'rtbcb' ); ?></h2>
<p class="description"><?php esc_html_e( 'Assess treasury maturity based on company data.', 'rtbcb' ); ?></p>
<p class="rtbcb-data-source">
    <span class="rtbcb-data-status rtbcb-status-treasury-maturity">⚪ <?php esc_html_e( 'Generate new', 'rtbcb' ); ?></span>
    <a href="#rtbcb-comprehensive-analysis" class="rtbcb-view-source" style="display:none;">
        <?php esc_html_e( 'View Source Data', 'rtbcb' ); ?>
    </a>
</p>
<?php $rtbcb_last = rtbcb_get_last_test_result( 'rtbcb-test-maturity-model', $test_results ?? [] ); ?>
<?php if ( $rtbcb_last ) : ?>
    <div class="notice notice-info" role="status">
        <p><strong><?php esc_html_e( 'Status:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['status'] ); ?></p>
        <p><strong><?php esc_html_e( 'Message:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['message'] ); ?></p>
        <p><strong><?php esc_html_e( 'Timestamp:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['timestamp'] ); ?></p>
        <p class="submit">
            <button type="button" class="button" id="rtbcb-rerun-maturity-model" data-section="rtbcb-test-maturity-model">
                <?php esc_html_e( 'Regenerate This Section Only', 'rtbcb' ); ?>
            </button>
        </p>
    </div>
<?php endif; ?>
<div class="card">
    <h3 class="title"><?php esc_html_e( 'Generate Assessment', 'rtbcb' ); ?></h3>
    <?php wp_nonce_field( 'rtbcb_test_maturity_model', 'rtbcb_test_maturity_model_nonce' ); ?>
    <p class="submit">
        <button type="button" id="rtbcb-generate-maturity-model" class="button button-primary">
            <?php esc_html_e( 'Assess Maturity', 'rtbcb' ); ?>
        </button>
    </p>
</div>
<div id="rtbcb-maturity-model-card" class="rtbcb-result-card" style="display:none;">
    <details>
        <summary><?php esc_html_e( 'Assessment', 'rtbcb' ); ?></summary>
        <div id="rtbcb-maturity-model-results"></div>
    </details>
</div>
<script>
    jQuery('#rtbcb-generate-maturity-model').on('click', function(){
        const btn = jQuery(this);
        const original = rtbcbTestUtils.showLoading(btn);
        jQuery.post(ajaxurl, {
            action: 'rtbcb_test_maturity_model',
            nonce: jQuery('#rtbcb_test_maturity_model_nonce').val()
        }).done(function(response){
            if (response.success) {
                const data = response.data;
                rtbcbTestUtils.renderSuccess(jQuery('#rtbcb-maturity-model-results'), data.assessment, null, data);
            jQuery('#rtbcb-maturity-model-card').show();
        } else {
            rtbcbTestUtils.renderError(jQuery('#rtbcb-maturity-model-results'), response.data.message);
        }
    }).always(function(){
        rtbcbTestUtils.hideLoading(btn, original);
    });
 });
 document.getElementById('rtbcb-rerun-maturity-model')?.addEventListener('click', function(){
    document.getElementById('rtbcb-generate-maturity-model').click();
 });
</script>
