<?php
/**
 * Request processing template.
 *
 * @package RealTreasuryBusinessCaseBuilder
 *
 * @var string $job_id Job identifier.
 */

defined( 'ABSPATH' ) || exit;

$ajax_url = admin_url( 'admin-ajax.php' );
$nonce    = wp_create_nonce( 'rtbcb_generate' );
?>
<div class="rtbcb-request-processing">
<p><?php esc_html_e( 'Your report is being generated. This page will refresh when ready.', 'rtbcb' ); ?></p>
</div>
<script>
document.addEventListener( 'DOMContentLoaded', function() {
var jobId = '<?php echo esc_js( $job_id ); ?>';
var ajaxUrl = '<?php echo esc_url( $ajax_url ); ?>';
var nonce = '<?php echo esc_js( $nonce ); ?>';

function poll() {
fetch( ajaxUrl + '?action=rtbcb_job_status&job_id=' + encodeURIComponent( jobId ) + '&rtbcb_nonce=' + nonce, {
credentials: 'same-origin',
headers: { 'X-Requested-With': 'XMLHttpRequest' }
} )
.then( function( response ) { return response.json(); } )
.then( function( data ) {
if ( data.success && data.data.status === 'completed' ) {
window.location.reload();
} else if ( data.success && data.data.status === 'error' ) {
document.querySelector( '.rtbcb-request-processing' ).textContent = data.data.message || '<?php echo esc_js( __( 'Processing failed.', 'rtbcb' ) ); ?>';
} else {
setTimeout( poll, 2000 );
}
} )
.catch( function() { setTimeout( poll, 2000 ); } );
}

poll();
} );
</script>

