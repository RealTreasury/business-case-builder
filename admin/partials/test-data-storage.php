<?php
/**
 * Partial for Test Data Storage section.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

defined( 'ABSPATH' ) || exit;

if ( ! rtbcb_require_completed_steps( 'rtbcb-test-data-storage', false ) ) {
    echo '<div class="notice notice-warning inline"><p>' .
        esc_html__( 'Please complete previous steps before accessing this section.', 'rtbcb' ) .
        '</p></div>';
    return;
}
?>
<h2><?php esc_html_e( 'Test Data Storage', 'rtbcb' ); ?></h2>
<p class="description"><?php esc_html_e( 'Verify saving and retrieving records using the plugin database layer.', 'rtbcb' ); ?></p>
<?php $rtbcb_last = rtbcb_get_last_test_result( 'rtbcb-test-data-storage', $test_results ?? [] ); ?>
<?php if ( $rtbcb_last ) : ?>
    <div class="notice notice-info" role="status">
        <p><strong><?php esc_html_e( 'Status:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['status'] ); ?></p>
        <p><strong><?php esc_html_e( 'Message:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['message'] ); ?></p>
        <p><strong><?php esc_html_e( 'Timestamp:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['timestamp'] ); ?></p>
        <p class="submit">
            <button type="button" class="button" id="rtbcb-rerun-data-storage" data-section="rtbcb-test-data-storage">
                <?php esc_html_e( 'Re-run', 'rtbcb' ); ?>
            </button>
        </p>
    </div>
<?php endif; ?>
<div class="card">
    <h3 class="title"><?php esc_html_e( 'Run Data Storage Test', 'rtbcb' ); ?></h3>
    <?php wp_nonce_field( 'rtbcb_test_data_storage', 'rtbcb_test_data_storage_nonce' ); ?>
    <p class="submit">
        <button type="button" id="rtbcb-run-data-storage" class="button button-primary">
            <?php esc_html_e( 'Run Test', 'rtbcb' ); ?>
        </button>
    </p>
</div>
<div id="rtbcb-data-storage-result" class="rtbcb-result-card"></div>
<script>
(function($){
    $('#rtbcb-run-data-storage, #rtbcb-rerun-data-storage').on('click', function(e){
        e.preventDefault();
        var nonce = $('#rtbcb_test_data_storage_nonce').val();
        var $btn = $('#rtbcb-run-data-storage');
        var original = $btn.text();
        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Testing...', 'rtbcb' ) ); ?>');
        $('#rtbcb-data-storage-result').html('');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'rtbcb_test_data_storage',
                nonce: nonce
            },
            success: function(response){
                if (response.success) {
                    $('#rtbcb-data-storage-result').html('<div class="notice notice-success"><p><?php echo esc_js( __( 'Lead saved with ID:', 'rtbcb' ) ); ?> '+response.data.lead_id+'</p></div>');
                } else {
                    $('#rtbcb-data-storage-result').html('<div class="notice notice-error"><p>'+ (response.data && response.data.message ? response.data.message : '<?php echo esc_js( __( 'Test failed.', 'rtbcb' ) ); ?>') +'</p></div>');
                }
            },
            error: function(){
                $('#rtbcb-data-storage-result').html('<div class="notice notice-error"><p><?php echo esc_js( __( 'Request failed.', 'rtbcb' ) ); ?></p></div>');
            },
            complete: function(){
                $btn.prop('disabled', false).text(original);
            }
        });
    });
})(jQuery);
</script>
