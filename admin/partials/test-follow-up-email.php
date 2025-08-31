<?php
/**
 * Follow-up email queue test.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

defined( 'ABSPATH' ) || exit;

if ( ! rtbcb_require_completed_steps( 'rtbcb-test-follow-up-email', false ) ) {
    echo '<div class="notice notice-warning inline"><p>' .
        esc_html__( 'Please complete previous steps before accessing this section.', 'rtbcb' ) .
        '</p></div>';
    return;
}

?>
<h2><?php esc_html_e( 'Follow-up Email Queue', 'rtbcb' ); ?></h2>
<p class="description"><?php esc_html_e( 'Trigger personalized follow-up emails and inspect queued messages.', 'rtbcb' ); ?></p>

<?php $rtbcb_last = rtbcb_get_last_test_result( 'rtbcb-test-follow-up-email', $test_results ?? [] ); ?>
<?php if ( $rtbcb_last ) : ?>
    <div class="notice notice-info" role="status">
        <p><strong><?php esc_html_e( 'Status:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['status'] ); ?></p>
        <p><strong><?php esc_html_e( 'Message:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['message'] ); ?></p>
        <p><strong><?php esc_html_e( 'Timestamp:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['timestamp'] ); ?></p>
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
        <button type="button" id="rtbcb-run-follow-up" class="button button-primary" data-section="rtbcb-test-follow-up-email">
            <?php esc_html_e( 'Queue Email', 'rtbcb' ); ?>
        </button>
    </p>
</div>
<div id="rtbcb-follow-up-result" class="rtbcb-result-card"></div>
