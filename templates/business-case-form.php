<?php
defined( 'ABSPATH' ) || exit;
?>
<div id="rtbcb-wizard-root">
       <form id="rtbcbForm" class="rtbcb-form rtbcb-wizard" method="post"></form>
</div>
<script>
document.addEventListener( 'DOMContentLoaded', function() {
       if ( window.renderRTBCBWizardForm ) {
               window.renderRTBCBWizardForm( 'rtbcb-wizard-root' );
       }
} );
</script>
