(function( window, $ ) {
    'use strict';

    const utils = {
        showLoading( button, loadingText ) {
            if ( ! button || ! button.length ) { return; }
            const original = button.data( 'rtbcb-original' ) || button.text();
            button.data( 'rtbcb-original', original );
            button.prop( 'disabled', true ).text( loadingText );
        },

        hideLoading( button ) {
            if ( ! button || ! button.length ) { return; }
            const original = button.data( 'rtbcb-original' ) || '';
            button.prop( 'disabled', false ).text( original );
        },

        renderSuccess( container, text, startTime, retryCallback ) {
            const wordCount = text.trim() ? text.trim().split( /\s+/ ).length : 0;
            const elapsed   = startTime ? ( ( performance.now() - startTime ) / 1000 ).toFixed( 2 ) : '0';
            const timestamp = new Date().toLocaleTimeString();
            const notice    = $( '<div />', { 'class': 'notice notice-success' } );
            notice.append( '<p><strong>Overview:</strong></p>' );
            notice.append( $( '<div />', {
                text: text,
                style: 'background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin-top: 10px;'
            } ) );
            notice.append( $( '<p />' ).text( 'Word count: ' + wordCount + ' | Time: ' + elapsed + 's | Timestamp: ' + timestamp ) );
            const actions = $( '<p />' );
            if ( retryCallback ) {
                const regen = $( '<button type="button" class="button" />' ).text( 'Regenerate' );
                regen.on( 'click', retryCallback );
                actions.append( regen );
            }
            const copy = $( '<button type="button" class="button" />' ).text( 'Copy' );
            copy.on( 'click', function() { utils.copyToClipboard( text ); } );
            actions.append( ' ' ).append( copy );
            notice.append( actions );
            container.html( notice );
        },

        renderError( container, message, retryCallback ) {
            const notice = $( '<div />', { 'class': 'notice notice-error' } );
            notice.append( $( '<p />' ).text( message ) );
            if ( retryCallback ) {
                const retry = $( '<button type="button" class="button" />' ).text( 'Retry' );
                retry.on( 'click', retryCallback );
                notice.append( $( '<p />' ).append( retry ) );
            }
            container.html( notice );
        },

        copyToClipboard( text ) {
            if ( navigator.clipboard && navigator.clipboard.writeText ) {
                navigator.clipboard.writeText( text ).then( function() {
                    alert( ( window.rtbcbAdmin && rtbcbAdmin.strings.copied ) || 'Copied to clipboard.' );
                } ).catch( function() {
                    utils.copyFallback( text );
                } );
            } else {
                utils.copyFallback( text );
            }
        },

        copyFallback( text ) {
            const textarea = $( '<textarea readonly />' ).css( {
                position: 'absolute',
                left: '-9999px'
            } ).val( text );
            $( 'body' ).append( textarea );
            textarea[0].select();
            try {
                document.execCommand( 'copy' );
                alert( ( window.rtbcbAdmin && rtbcbAdmin.strings.copied ) || 'Copied to clipboard.' );
            } catch ( err ) {
                alert( ( window.rtbcbAdmin && rtbcbAdmin.strings.error ) + ' ' + err.message );
            }
            textarea.remove();
        }
    };

    window.rtbcbTestUtils = utils;

})( window, jQuery );
