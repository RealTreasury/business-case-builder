<?php
/**
 * Partial: API Test section.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<h2><?php esc_html_e( 'OpenAI API Test', 'rtbcb' ); ?></h2>

<div id="rtbcb-test-results" style="margin: 20px 0;">
    <p><?php esc_html_e( 'Click the button below to test your OpenAI API connection:', 'rtbcb' ); ?></p>
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

        $.post(ajaxurl, {
            action: 'rtbcb_test_api',
            nonce: '<?php echo wp_create_nonce( 'rtbcb_test_api' ); ?>'
        }).done(function(response){
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
        }).fail(function(){
            $results.html('<div class="notice notice-error"><p><strong>❌ <?php echo esc_js( __( 'Request failed', 'rtbcb' ) ); ?></strong></p><p><?php echo esc_js( __( 'Unable to connect to WordPress AJAX handler.', 'rtbcb' ) ); ?></p></div>');
        }).always(function(){
            $btn.prop('disabled', false).text('<?php echo esc_js( __( 'Test API Connection', 'rtbcb' ) ); ?>');
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
