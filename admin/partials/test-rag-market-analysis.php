<?php
/**
	* Partial for Test RAG Market Analysis section.
	*
	* @package RealTreasuryBusinessCaseBuilder
	*/

defined( 'ABSPATH' ) || exit;

if ( ! rtbcb_require_completed_steps( 'rtbcb-test-rag-market-analysis', false ) ) {
	echo '<div class="notice notice-warning inline"><p>' .
		esc_html__( 'Please complete previous steps before accessing this section.', 'rtbcb' ) .
		'</p></div>';
	return;
}

?>
<h2><?php esc_html_e( 'Test RAG Market Analysis', 'rtbcb' ); ?></h2>
<p class="description"><?php esc_html_e( 'Run a retrieval-augmented query and view a vendor shortlist.', 'rtbcb' ); ?></p>
<p class="rtbcb-data-source">
	<span class="rtbcb-data-status rtbcb-status-market-analysis">⚪ <?php esc_html_e( 'Generate new', 'rtbcb' ); ?></span>
	<a href="#rtbcb-comprehensive-analysis" class="rtbcb-view-source" style="display:none;">
		<?php esc_html_e( 'View Source Data', 'rtbcb' ); ?>
	</a>
</p>
<?php $rtbcb_last = rtbcb_get_last_test_result( 'rtbcb-test-rag-market-analysis', $test_results ?? [] ); ?>
<?php if ( $rtbcb_last ) : ?>
	<div class="notice notice-info" role="status">
		<p><strong><?php esc_html_e( 'Status:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['status'] ); ?></p>
		<p><strong><?php esc_html_e( 'Message:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['message'] ); ?></p>
		<p><strong><?php esc_html_e( 'Timestamp:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['timestamp'] ); ?></p>
		<p class="submit">
			<button type="button" class="button" id="rtbcb-rerun-rag-market-analysis" data-section="rtbcb-test-rag-market-analysis">
				<?php esc_html_e( 'Regenerate This Section Only', 'rtbcb' ); ?>
			</button>
		</p>
	</div>
<?php endif; ?>
<div class="card">
	<h3 class="title"><?php esc_html_e( 'Run Market Analysis', 'rtbcb' ); ?></h3>
	<?php wp_nonce_field( 'rtbcb_test_rag_market_analysis', 'rtbcb_test_rag_market_analysis_nonce' ); ?>
	<p class="submit">
		<button type="button" id="rtbcb-run-rag-analysis" class="button button-primary">
			<?php esc_html_e( 'Run Analysis', 'rtbcb' ); ?>
		</button>
	</p>
</div>
<div id="rtbcb-rag-market-analysis-card" class="rtbcb-result-card" style="display:none;">
	<details>
		<summary><?php esc_html_e( 'Vendor Shortlist', 'rtbcb' ); ?></summary>
		<ul id="rtbcb-rag-market-analysis-results"></ul>
	</details>
</div>
<script>
	jQuery('#rtbcb-run-rag-analysis').on('click', function(){
		const btn = jQuery(this);
		const original = rtbcbTestUtils.showLoading(btn);
		jQuery.post(ajaxurl, {
			action: 'rtbcb_test_rag_market_analysis',
			nonce: jQuery('#rtbcb_test_rag_market_analysis_nonce').val()
		}).done(function(response){
			if (response.success) {
				const list = jQuery('#rtbcb-rag-market-analysis-results').empty();
				response.data.vendors.forEach(function(v){
				list.append('<li>' + v + '</li>');
			});
			jQuery('#rtbcb-rag-market-analysis-card').show();
		} else {
			rtbcbTestUtils.renderError(jQuery('#rtbcb-rag-market-analysis-results'), response.data.message);
		}
	}).always(function(){
		rtbcbTestUtils.hideLoading(btn, original);
	});
	});
	document.getElementById('rtbcb-rerun-rag-market-analysis')?.addEventListener('click', function(){
	document.getElementById('rtbcb-run-rag-analysis').click();
	});
</script>
