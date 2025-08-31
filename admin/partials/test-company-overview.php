<?php
/**
	* Partial for Test Company Overview section.
	*
	* @package RealTreasuryBusinessCaseBuilder
	*/

defined( 'ABSPATH' ) || exit;

if ( ! rtbcb_require_completed_steps( 'rtbcb-test-company-overview', false ) ) {
	echo '<div class="notice notice-warning inline"><p>' .
		esc_html__( 'Please complete previous steps before accessing this section.', 'rtbcb' ) .
		'</p></div>';
	return;
}
?>
<h2><?php esc_html_e( 'Test Company Overview', 'rtbcb' ); ?></h2>
<p class="description"><?php esc_html_e( 'Generate a concise company profile using your configured AI model.', 'rtbcb' ); ?></p>
<p class="rtbcb-data-source">
	<span class="rtbcb-data-status rtbcb-status-company-overview">⚪ <?php esc_html_e( 'Generate new', 'rtbcb' ); ?></span>
	<a href="#rtbcb-comprehensive-analysis" class="rtbcb-view-source" style="display:none;">
		<?php esc_html_e( 'View Source Data', 'rtbcb' ); ?>
	</a>
</p>
<?php $rtbcb_last = rtbcb_get_last_test_result( 'rtbcb-test-company-overview', $test_results ?? [] ); ?>
<?php if ( $rtbcb_last ) : ?>
	<div class="notice notice-info" role="status">
		<p><strong><?php esc_html_e( 'Status:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['status'] ); ?></p>
		<p><strong><?php esc_html_e( 'Message:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['message'] ); ?></p>
		<p><strong><?php esc_html_e( 'Timestamp:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['timestamp'] ); ?></p>
		<p class="submit">
			<button type="button" class="button" id="rtbcb-rerun-company-overview" data-section="rtbcb-test-company-overview">
				<?php esc_html_e( 'Regenerate This Section Only', 'rtbcb' ); ?>
			</button>
		</p>
	</div>
<?php endif; ?>
<div class="card">
	<h3 class="title"><?php esc_html_e( 'Generate Company Overview', 'rtbcb' ); ?></h3>
	<p><?php esc_html_e( 'Use the company name set above to generate an AI-powered overview using the configured LLM.', 'rtbcb' ); ?></p>
	<p class="submit">
		<button type="button" id="rtbcb-generate-company-overview" class="button button-primary">
			<?php esc_html_e( 'Generate Overview', 'rtbcb' ); ?>
		</button>
		<button type="button" id="rtbcb-clear-company-overview" class="button">
			<?php esc_html_e( 'Clear', 'rtbcb' ); ?>
		</button>
	</p>
</div>
<div id="rtbcb-company-overview-card" class="rtbcb-result-card" style="display:none;">
	<details>
		<summary><?php esc_html_e( 'Generated Overview', 'rtbcb' ); ?></summary>
		<div id="<?php echo esc_attr( 'rtbcb-company-overview-results' ); ?>"></div>
		<div id="<?php echo esc_attr( 'rtbcb-company-overview-meta' ); ?>" class="rtbcb-meta"></div>
		<p class="rtbcb-actions">
			<button type="button" id="rtbcb-regenerate-company-overview" class="button">
				<?php esc_html_e( 'Regenerate This Section Only', 'rtbcb' ); ?>
			</button>
			<button type="button" id="rtbcb-copy-company-overview" class="button">
				<?php esc_html_e( 'Copy to Clipboard', 'rtbcb' ); ?>
			</button>
		</p>
	</details>
</div>
<style>
#rtbcb-company-overview-card details {
	margin-top: 20px;
}
#rtbcb-company-overview-results div[style*="background"] {
	white-space: pre-wrap;
	line-height: 1.6;
}
#rtbcb-company-overview-meta {
	margin-top: 10px;
}
</style>
<script>
	document.getElementById( 'rtbcb-rerun-company-overview' )?.addEventListener( 'click', function() {
		document.getElementById( 'rtbcb-generate-company-overview' ).click();
	});
</script>
