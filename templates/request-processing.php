<?php
defined( 'ABSPATH' ) || exit;
/**
 * Request processing template.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

$job_id = isset( $job_id ) ? $job_id : '';
?>
<div class="rtbcb-request-processing">
<h2><?php esc_html_e( 'Generating your business case', 'rtbcb' ); ?></h2>
<p id="rtbcb-progress-message"><?php esc_html_e( 'Please wait while we prepare your report...', 'rtbcb' ); ?></p>
</div>
<script type="text/javascript">
(function() {
const ajaxUrl = ( typeof rtbcbAjax !== 'undefined' && rtbcbAjax.ajax_url ) ? rtbcbAjax.ajax_url : '';
const nonce = ( typeof rtbcbAjax !== 'undefined' && rtbcbAjax.nonce ) ? rtbcbAjax.nonce : '';
const jobId = '<?php echo esc_js( $job_id ); ?>';

async function poll() {
try {
const response = await fetch(`${ajaxUrl}?action=rtbcb_job_status&job_id=${encodeURIComponent(jobId)}&rtbcb_nonce=${nonce}`, {
credentials: 'same-origin',
headers: { 'X-Requested-With': 'XMLHttpRequest' }
});
const data = await response.json();
if (data.success) {
if (data.data && data.data.status === 'completed') {
window.location.reload();
return;
}
const msg = data.data.step || data.data.message;
if (msg) {
document.getElementById('rtbcb-progress-message').textContent = msg;
}
}
} catch (e) {}
setTimeout(poll, 2000);
}

poll();
})();
</script>
