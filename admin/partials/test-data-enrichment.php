<?php
/**
 * Partial for Test Data Enrichment section.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! rtbcb_require_completed_steps( 'rtbcb-test-data-enrichment', false ) ) {
    echo '<div class="notice notice-warning inline"><p>' .
        esc_html__( 'Please complete previous steps before accessing this section.', 'rtbcb' ) .
        '</p></div>';
    return;
}
?>
<h2><?php esc_html_e( 'Test Data Enrichment', 'rtbcb' ); ?></h2>
<p class="description"><?php esc_html_e( 'Validate LLM-based enrichment using sample inputs.', 'rtbcb' ); ?></p>
<?php $rtbcb_last = rtbcb_get_last_test_result( 'rtbcb-test-data-enrichment', $test_results ?? [] ); ?>
<?php if ( $rtbcb_last ) : ?>
    <div class="notice notice-info" role="status">
        <p><strong><?php esc_html_e( 'Status:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['status'] ); ?></p>
        <p><strong><?php esc_html_e( 'Message:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['message'] ); ?></p>
        <p><strong><?php esc_html_e( 'Timestamp:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['timestamp'] ); ?></p>
        <p class="submit">
            <button type="button" class="button" id="rtbcb-rerun-data-enrichment" data-section="rtbcb-test-data-enrichment">
                <?php esc_html_e( 'Re-run', 'rtbcb' ); ?>
            </button>
        </p>
    </div>
<?php endif; ?>
<div class="card">
    <h3 class="title"><?php esc_html_e( 'Run Data Enrichment', 'rtbcb' ); ?></h3>
    <p><?php esc_html_e( 'Use the configured language model to enrich sample data.', 'rtbcb' ); ?></p>
    <?php wp_nonce_field( 'rtbcb_test_data_enrichment', 'rtbcb_test_data_enrichment_nonce' ); ?>
    <p class="submit">
        <button type="button" id="rtbcb-run-data-enrichment" class="button button-primary">
            <?php esc_html_e( 'Run Test', 'rtbcb' ); ?>
        </button>
    </p>
</div>
<div id="rtbcb-data-enrichment-result" class="rtbcb-result-card"></div>
<script>
(function($){
    $('#rtbcb-run-data-enrichment, #rtbcb-rerun-data-enrichment').on('click', function(e){
        e.preventDefault();
        var nonce = $('#rtbcb_test_data_enrichment_nonce').val();
        var $btn = $('#rtbcb-run-data-enrichment');
        var original = $btn.text();
        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Testing...', 'rtbcb' ) ); ?>');
        $('#rtbcb-data-enrichment-result').html('');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'rtbcb_test_data_enrichment',
                nonce: nonce
            },
            success: function(response){
                if (response.success) {
                    $('#rtbcb-data-enrichment-result').html('<pre>'+JSON.stringify(response.data.analysis, null, 2)+'</pre>');
                } else {
                    $('#rtbcb-data-enrichment-result').html('<div class="notice notice-error"><p>'+ (response.data && response.data.message ? response.data.message : '<?php echo esc_js( __( 'Test failed.', 'rtbcb' ) ); ?>') +'</p></div>');
                }
            },
            error: function(){
                $('#rtbcb-data-enrichment-result').html('<div class="notice notice-error"><p><?php echo esc_js( __( 'Request failed.', 'rtbcb' ) ); ?></p></div>');
            },
            complete: function(){
                $btn.prop('disabled', false).text(original);
            }
        });
    });
})(jQuery);
</script>
