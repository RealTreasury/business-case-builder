<?php
defined( 'ABSPATH' ) || exit;

/**
 * Request processing template that polls job status and reloads when complete.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

$job_id = isset( $_GET['job_id'] ) ? sanitize_text_field( wp_unslash( $_GET['job_id'] ) ) : '';

if ( empty( $job_id ) ) {
	echo esc_html__( 'Missing job ID.', 'rtbcb' );
	return;
}

$status = RTBCB_Background_Job::get_status( $job_id );
if ( ! is_wp_error( $status ) && 'completed' === ( $status['status'] ?? '' ) && ! empty( $status['result']['report_html'] ) ) {
	echo $status['result']['report_html'];
	return;
}
?>
<div class="rtbcb-request-processing">
	<p id="rtbcb-processing-message"><?php esc_html_e( 'Processing your request...', 'rtbcb' ); ?></p>
</div>
<script>
(function(){
	const jobId = <?php echo wp_json_encode( $job_id ); ?>;
	const ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
	const nonce = <?php echo wp_json_encode( wp_create_nonce( 'rtbcb_generate' ) ); ?>;
	const msgEl = document.getElementById('rtbcb-processing-message');
	function check(){
	    fetch(`${ajaxUrl}?action=rtbcb_job_status&job_id=${encodeURIComponent(jobId)}&rtbcb_nonce=${nonce}`)
	        .then(r=>r.json())
	        .then(data=>{
	            if(data.success && data.data){
	                if(data.data.status==='completed'){
	                    window.location.reload();
	                }else if(data.data.status==='error'){
	                    msgEl.textContent=data.data.message||<?php echo wp_json_encode( __( 'An error occurred.', 'rtbcb' ) ); ?>;
	                }else{
	                    setTimeout(check,2000);
	                }
	            }else{
	                setTimeout(check,2000);
	            }
	        })
	        .catch(()=>setTimeout(check,2000));
	}
	check();
})();
</script>
