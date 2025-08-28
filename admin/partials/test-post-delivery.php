<?php
/**
 * Post-delivery engagement tests.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<h2><?php esc_html_e( 'Post-Delivery Engagement', 'rtbcb' ); ?></h2>
<p class="description">
    <?php esc_html_e( 'Diagnostics for engagement tracking and follow-up sequences.', 'rtbcb' ); ?>
</p>

<?php if ( rtbcb_require_completed_steps( 'rtbcb-test-tracking-script' ) ) : ?>
    <?php $rtbcb_last = rtbcb_get_last_test_result( 'rtbcb-test-tracking-script', $test_results ?? [] ); ?>
    <?php if ( $rtbcb_last ) : ?>
        <div class="notice notice-info" role="status">
            <p><strong><?php esc_html_e( 'Status:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['status'] ); ?></p>
            <p><strong><?php esc_html_e( 'Message:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['message'] ); ?></p>
            <p><strong><?php esc_html_e( 'Timestamp:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['timestamp'] ); ?></p>
            <p class="submit">
                <button type="button" class="button" id="rtbcb-rerun-tracking-script" data-section="rtbcb-test-tracking-script">
                    <?php esc_html_e( 'Re-run', 'rtbcb' ); ?>
                </button>
            </p>
        </div>
    <?php endif; ?>
    <div class="card">
        <h3 class="title"><?php esc_html_e( 'Tracking Script Injection', 'rtbcb' ); ?></h3>
        <p><?php esc_html_e( 'Paste a tracking script snippet and verify it fires a test event.', 'rtbcb' ); ?></p>
        <textarea id="rtbcb-tracking-snippet" class="large-text" rows="4" placeholder="<?php esc_attr_e( 'Paste script snippet…', 'rtbcb' ); ?>"></textarea>
        <?php wp_nonce_field( 'rtbcb_test_tracking_script', 'rtbcb_test_tracking_script_nonce' ); ?>
        <p class="submit">
            <button type="button" id="rtbcb-run-tracking-script" class="button button-primary">
                <?php esc_html_e( 'Inject &amp; Test', 'rtbcb' ); ?>
            </button>
        </p>
    </div>
    <div id="rtbcb-tracking-script-result" class="rtbcb-result-card"></div>
<?php endif; ?>

<?php if ( rtbcb_require_completed_steps( 'rtbcb-test-follow-up-email' ) ) : ?>
    <?php $rtbcb_last_email = rtbcb_get_last_test_result( 'rtbcb-test-follow-up-email', $test_results ?? [] ); ?>
    <?php if ( $rtbcb_last_email ) : ?>
        <div class="notice notice-info" role="status">
            <p><strong><?php esc_html_e( 'Status:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last_email['status'] ); ?></p>
            <p><strong><?php esc_html_e( 'Message:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last_email['message'] ); ?></p>
            <p><strong><?php esc_html_e( 'Timestamp:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last_email['timestamp'] ); ?></p>
            <p class="submit">
                <button type="button" class="button" id="rtbcb-rerun-follow-up" data-section="rtbcb-test-follow-up-email">
                    <?php esc_html_e( 'Re-run', 'rtbcb' ); ?>
                </button>
            </p>
        </div>
    <?php endif; ?>
    <div class="card">
        <h3 class="title"><?php esc_html_e( 'Follow-up Email Queue', 'rtbcb' ); ?></h3>
        <p><?php esc_html_e( 'Trigger personalized follow-up emails and inspect queued messages.', 'rtbcb' ); ?></p>
        <?php wp_nonce_field( 'rtbcb_test_follow_up_email', 'rtbcb_test_follow_up_email_nonce' ); ?>
        <p class="submit">
            <button type="button" id="rtbcb-run-follow-up" class="button button-primary">
                <?php esc_html_e( 'Queue Email', 'rtbcb' ); ?>
            </button>
        </p>
    </div>
    <div id="rtbcb-follow-up-result" class="rtbcb-result-card"></div>
<?php endif; ?>

<script>
(function($){
    $('#rtbcb-rerun-tracking-script').on('click', function(){
        $('#rtbcb-run-tracking-script').trigger('click');
    });
    $('#rtbcb-rerun-follow-up').on('click', function(){
        $('#rtbcb-run-follow-up').trigger('click');
    });
    $('#rtbcb-run-tracking-script').on('click', function(e){
        e.preventDefault();
        var nonce   = $('#rtbcb_test_tracking_script_nonce').val();
        var snippet = $('#rtbcb-tracking-snippet').val();
        var $btn    = $(this);
        var original = $btn.text();
        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Testing...', 'rtbcb' ) ); ?>');
        $('#rtbcb-tracking-script-result').html('');
        try {
            var script = document.createElement('script');
            script.text = snippet + '\n document.dispatchEvent(new CustomEvent("rtbcbTrackingEvent"));';
            document.body.appendChild(script);
        } catch(err) {
            $('#rtbcb-tracking-script-result').html('<div class="notice notice-error"><p>'+ err.message +'</p></div>');
            $btn.prop('disabled', false).text(original);
            return;
        }
        $(document).one('rtbcbTrackingEvent', function(){
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'rtbcb_test_tracking_script',
                    nonce: nonce
                },
                success: function(response){
                    if (response.success) {
                        $('#rtbcb-tracking-script-result').html('<div class="notice notice-success"><p>'+ <?php echo json_encode( __( 'Event captured.', 'rtbcb' ) ); ?> +'</p></div>');
                    } else {
                        $('#rtbcb-tracking-script-result').html('<div class="notice notice-error"><p>'+ (response.data && response.data.message ? response.data.message : <?php echo json_encode( __( 'Test failed.', 'rtbcb' ) ); ?>) +'</p></div>');
                    }
                },
                error: function(){
                    $('#rtbcb-tracking-script-result').html('<div class="notice notice-error"><p>'+ <?php echo json_encode( __( 'Request failed.', 'rtbcb' ) ); ?> +'</p></div>');
                },
                complete: function(){
                    $btn.prop('disabled', false).text(original);
                }
            });
        });
    });
    $('#rtbcb-run-follow-up').on('click', function(e){
        e.preventDefault();
        var nonce = $('#rtbcb_test_follow_up_email_nonce').val();
        var $btn = $(this);
        var original = $btn.text();
        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Testing...', 'rtbcb' ) ); ?>');
        $('#rtbcb-follow-up-result').html('');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'rtbcb_test_follow_up_email',
                nonce: nonce
            },
            success: function(response){
                if (response.success) {
                    var queue = response.data.queue || [];
                    $('#rtbcb-follow-up-result').html('<pre>'+ JSON.stringify(queue, null, 2) +'</pre>');
                } else {
                    $('#rtbcb-follow-up-result').html('<div class="notice notice-error"><p>'+ (response.data && response.data.message ? response.data.message : <?php echo json_encode( __( 'Test failed.', 'rtbcb' ) ); ?>) +'</p></div>');
                }
            },
            error: function(){
                $('#rtbcb-follow-up-result').html('<div class="notice notice-error"><p>'+ <?php echo json_encode( __( 'Request failed.', 'rtbcb' ) ); ?> +'</p></div>');
            },
            complete: function(){
                $btn.prop('disabled', false).text(original);
            }
        });
    });
})(jQuery);
</script>

