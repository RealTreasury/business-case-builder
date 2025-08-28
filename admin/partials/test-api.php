<?php
/**
 * Partial for OpenAI API Test section.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<h2><?php esc_html_e( 'OpenAI API Test', 'rtbcb' ); ?></h2>
<p class="description"><?php esc_html_e( 'Verify that your API credentials and network connection are working properly.', 'rtbcb' ); ?></p>
<?php $rtbcb_last = rtbcb_get_last_test_result( 'rtbcb-api-test', $test_results ?? [] ); ?>
<?php if ( $rtbcb_last ) : ?>
    <div class="notice notice-info" role="status">
        <p><strong><?php esc_html_e( 'Status:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['status'] ); ?></p>
        <p><strong><?php esc_html_e( 'Message:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['message'] ); ?></p>
        <p><strong><?php esc_html_e( 'Timestamp:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['timestamp'] ); ?></p>
        <p class="submit">
            <button type="button" class="button" onclick="document.getElementById('rtbcb-api-test-card').scrollIntoView();">
                <?php esc_html_e( 'View Details', 'rtbcb' ); ?>
            </button>
        </p>
    </div>
<?php endif; ?>
<div id="rtbcb-api-test-card" class="rtbcb-result-card">
    <details>
        <summary><?php esc_html_e( 'Test Output', 'rtbcb' ); ?></summary>
        <div id="rtbcb-test-results" style="margin: 20px 0;">
            <p><?php esc_html_e( 'Click the button below to test your OpenAI API connection:', 'rtbcb' ); ?></p>
        </div>
    </details>
</div>
<button type="button" id="rtbcb-test-api-btn" class="button button-primary">
    <?php esc_html_e( 'Test API Connection', 'rtbcb' ); ?>
</button>
<script>
jQuery(function($){
    $('#rtbcb-test-api-btn').on('click', function(){
        var $btn = $(this);
        var $results = $('#rtbcb-test-results');
        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Testing...', 'rtbcb' ) ); ?>');
        $results.html('<p><?php echo esc_js( __( 'Testing OpenAI API connection...', 'rtbcb' ) ); ?></p>');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'rtbcb_test_api',
                nonce: '<?php echo wp_create_nonce( 'rtbcb_test_api' ); ?>'
            },
            async: false,
            success: function(response){
                if (response.success) {
                    var html = '<div class="notice notice-success"><p><strong><?php echo esc_js( '✅ ' . __( 'OpenAI API connection successful', 'rtbcb' ) ); ?></strong></p>' +
                        '<p>' + response.data.details + '</p>';
                    if (response.data.models_available) {
                        html += '<p><strong><?php echo esc_js( __( 'Available models:', 'rtbcb' ) ); ?></strong> ' + response.data.models_available.join(', ') + '</p>';
                    }
                    html += '</div>';
                    $results.html(html);
                } else {
                    var errorHtml = '<div class="notice notice-error"><p><strong>❌ ' + response.data.message + '</strong></p>' +
                        '<p>' + response.data.details + '</p>';
                    if (response.data.http_code) {
                        errorHtml += '<p><?php echo esc_js( __( 'HTTP Code:', 'rtbcb' ) ); ?> ' + response.data.http_code + '</p>';
                    }
                    errorHtml += '</div>';
                    $results.html(errorHtml);
                }
            },
            error: function(){
                $results.html('<div class="notice notice-error"><p><strong>❌ <?php echo esc_js( __( 'Request failed', 'rtbcb' ) ); ?></strong></p><p><?php echo esc_js( __( 'Unable to connect to WordPress AJAX handler.', 'rtbcb' ) ); ?></p></div>');
            },
            complete: function(){
                $btn.prop('disabled', false).text('<?php echo esc_js( __( 'Test API Connection', 'rtbcb' ) ); ?>');
            }
        });
    });
});
</script>
<style>
#rtbcb-test-results .notice {
    padding: 15px;
    margin: 15px 0;
}
#rtbcb-test-results .notice-success {
    border-left: 4px solid #00a32a;
    background: #f0f8ff;
}
#rtbcb-test-results .notice-error {
    border-left: 4px solid #d63638;
    background: #fff8f8;
}
</style>
