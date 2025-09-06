<?php
defined( 'ABSPATH' ) || exit;
?>
<form id="rtbcbForm" class="rtbcb-form rtbcb-wizard"></form>
<script>
( function renderWhenReady() {
	if ( window.renderRTBCBWizardForm ) {
		window.renderRTBCBWizardForm( 'rtbcbForm' );
		return;
	}
	setTimeout( renderWhenReady, 50 );
} )();
</script>
