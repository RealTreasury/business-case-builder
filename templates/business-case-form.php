<?php
defined( 'ABSPATH' ) || exit;
?>
<form id="rtbcbForm" class="rtbcb-form rtbcb-wizard"></form>
<script>
document.addEventListener( 'DOMContentLoaded', function() {
	if ( window.renderRTBCBWizardForm ) {
		window.renderRTBCBWizardForm( 'rtbcbForm' );
	}
} );
</script>
