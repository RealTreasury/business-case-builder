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
        <div id="rtbcb-test-results">
            <p><?php esc_html_e( 'Click the button below to test your OpenAI API connection:', 'rtbcb' ); ?></p>
        </div>
    </details>
</div>
<button
    type="button"
    id="rtbcb-test-api-btn"
    class="button button-primary"
    data-nonce="<?php echo esc_attr( wp_create_nonce( 'rtbcb_test_api' ) ); ?>"
    data-testing="<?php echo esc_attr__( 'Testing...', 'rtbcb' ); ?>"
    data-testing-msg="<?php echo esc_attr__( 'Testing OpenAI API connection...', 'rtbcb' ); ?>"
    data-success="<?php echo esc_attr__( '✅ OpenAI API connection successful', 'rtbcb' ); ?>"
    data-available="<?php echo esc_attr__( 'Available models:', 'rtbcb' ); ?>"
    data-http="<?php echo esc_attr__( 'HTTP Code:', 'rtbcb' ); ?>"
    data-fail="<?php echo esc_attr__( 'Request failed', 'rtbcb' ); ?>"
    data-ajax-fail="<?php echo esc_attr__( 'Unable to connect to WordPress AJAX handler.', 'rtbcb' ); ?>"
    data-label="<?php echo esc_attr__( 'Test API Connection', 'rtbcb' ); ?>"
>
    <?php esc_html_e( 'Test API Connection', 'rtbcb' ); ?>
</button>
