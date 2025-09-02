(function( $ ) {
	'use strict';

	const { __ } = wp.i18n;

	$( function() {
		$( '#rtbcb-debug-toggle' ).on( 'click', function() {
			$( '#rtbcb-debug-content' ).toggle();
		} );

		$( '.rtbcb-debug-button' ).on( 'click', function( e ) {
			e.preventDefault();
			const action = $( this ).data( 'action' );
			const result = $( '#' + action + '_result' );
			result.text( __( 'Running...', 'rtbcb' ) );
			$.post( ajaxurl, { action } )
				.done( function( response ) {
				    let output = response;
				    if ( response && response.data ) {
				        output = response.data;
				    }
				    if ( typeof output === 'object' ) {
				        output = JSON.stringify( output, null, 2 );
				    }
				    result.text( output );
				} )
				.fail( function() {
				    result.text( __( 'Request failed', 'rtbcb' ) );
				} );
		} );
	} );
})( jQuery );

