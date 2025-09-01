<?php
defined( 'ABSPATH' ) || exit;

/**
 * Tracking script injection test.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! rtbcb_require_completed_steps( 'rtbcb-test-tracking-script', false ) ) {
	echo '<div class="notice notice-warning inline"><p>' .
		esc_html__( 'Please complete previous steps before accessing this section.', 'rtbcb' ) .
		'</p></div>';
	return;
}

?>
<h2><?php esc_html_e( 'Tracking Script Injection', 'rtbcb' ); ?></h2>
<p class="description"><?php esc_html_e( 'Paste a tracking script snippet and verify it fires a test event.', 'rtbcb' ); ?></p>

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
		<button type="button" id="rtbcb-run-tracking-script" class="button button-primary" data-section="rtbcb-test-tracking-script">
			<?php esc_html_e( 'Inject &amp; Test', 'rtbcb' ); ?>
		</button>
	</p>
</div>
<div id="rtbcb-tracking-script-result" class="rtbcb-result-card"></div>
