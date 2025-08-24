jQuery( function( $ ) {
    var latestReportText = '';
    function setStatus( message, isError, retryFn ) {
        var status = $( '#rtbcb-report-status' );
        status.removeClass( 'error' ).empty();
        if ( message ) {
            status.text( message );
            if ( isError && retryFn ) {
                var retry = $( '<button />', {
                    class: 'button rtbcb-retry',
                    text: rtbcbAdmin.strings.retry
                } );
                retry.on( 'click', function( e ) {
                    e.preventDefault();
                    retryFn();
                } );
                status.append( ' ' ).append( retry );
            }
            if ( isError ) {
                status.addClass( 'error' );
            }
        }
    }

    function updateSectionMeta( section, data ) {
        var container = $( '#rtbcb-section-' + section );
        if ( data.word_count !== undefined ) {
            container.find( '.rtbcb-word-count' ).text( data.word_count );
        }
        container.find( '.rtbcb-generated' ).text( data.generated );
        container.find( '.rtbcb-elapsed' ).text( data.elapsed + 's' );
    }

    function generateReport() {
        var company = $( '#rtbcb-company-name' ).val().trim();
        var focusRaw = $( '#rtbcb-focus-areas' ).val().trim();
        var complexity = $( '#rtbcb-complexity' ).val();
        if ( ! company || ! focusRaw ) {
            setStatus( rtbcbAdmin.strings.error, true );
            return;
        }
        var focus = focusRaw.split( ',' ).map( function( s ) {
            return s.trim();
        } ).filter( Boolean );

        setStatus( rtbcbAdmin.strings.generating );
        $( '#rtbcb-report-sections, #rtbcb-report-actions, #rtbcb-report-summary' ).hide();

        $.post( rtbcbAdmin.ajax_url, {
            action: 'rtbcb_test_generate_complete_report',
            nonce: rtbcbAdmin.complete_report_nonce,
            company_name: company,
            focus_areas: focus,
            complexity: complexity
        } ).done( function( resp ) {
            if ( resp.success ) {
                setStatus( '' );
                var d = resp.data;
                $( '#rtbcb-report-preview' ).html( d.html );
                $( '#rtbcb-section-company_overview .rtbcb-section-content' ).text( d.sections.company_overview );
                updateSectionMeta( 'company_overview', {
                    word_count: d.word_counts.company_overview,
                    elapsed: d.timestamps.per_section.company_overview,
                    generated: new Date( d.timestamps.end * 1000 ).toLocaleString()
                } );
                $( '#rtbcb-section-treasury_tech_overview .rtbcb-section-content' ).text( d.sections.treasury_tech_overview );
                updateSectionMeta( 'treasury_tech_overview', {
                    word_count: d.word_counts.treasury_tech_overview,
                    elapsed: d.timestamps.per_section.treasury_tech_overview,
                    generated: new Date( d.timestamps.end * 1000 ).toLocaleString()
                } );
                $( '#rtbcb-section-roi .rtbcb-section-content' ).text( JSON.stringify( d.sections.roi ) );
                updateSectionMeta( 'roi', {
                    word_count: 0,
                    elapsed: d.timestamps.per_section.roi,
                    generated: new Date( d.timestamps.end * 1000 ).toLocaleString()
                } );
                latestReportText = d.sections.company_overview + '\n\n' + d.sections.treasury_tech_overview + '\n\n' + JSON.stringify( d.sections.roi );
                $( '#rtbcb-summary-word-count' ).text( d.word_counts.combined );
                $( '#rtbcb-summary-generated' ).text( new Date( d.timestamps.end * 1000 ).toLocaleString() );
                $( '#rtbcb-summary-elapsed' ).text( d.timestamps.elapsed.toFixed( 2 ) + 's' );
                if ( d.download_url ) {
                    $( '#rtbcb-export-html' ).attr( 'href', d.download_url );
                }
                $( '#rtbcb-report-sections, #rtbcb-report-actions, #rtbcb-report-summary' ).show();
            } else {
                setStatus( resp.data && resp.data.message ? resp.data.message : rtbcbAdmin.strings.error, true, generateReport );
            }
        } ).fail( function() {
            setStatus( rtbcbAdmin.strings.error, true, generateReport );
        } );
    }

    $( '#rtbcb-generate-report' ).on( 'click', function( e ) {
        e.preventDefault();
        generateReport();
    } );

    $( '.rtbcb-regenerate' ).on( 'click', function( e ) {
        e.preventDefault();
        var section = $( this ).data( 'section' );
        if ( 'company_overview' === section ) {
            regenerateCompany();
        } else if ( 'treasury_tech_overview' === section ) {
            regenerateTech();
        } else if ( 'roi' === section ) {
            regenerateROI();
        }
    } );

    $( '.rtbcb-copy' ).on( 'click', function( e ) {
        e.preventDefault();
        var section = $( this ).data( 'section' );
        var text = $( '#rtbcb-section-' + section + ' .rtbcb-section-content' ).text();
        if ( navigator.clipboard ) {
            navigator.clipboard.writeText( text ).then( function() {
                alert( rtbcbAdmin.strings.copied );
            } ).catch( function() {
                alert( rtbcbAdmin.strings.error );
            } );
        }
    } );

    $( '#rtbcb-copy-report' ).on( 'click', function( e ) {
        e.preventDefault();
        if ( navigator.clipboard && latestReportText ) {
            navigator.clipboard.writeText( latestReportText ).then( function() {
                alert( rtbcbAdmin.strings.copied );
            } ).catch( function() {
                alert( rtbcbAdmin.strings.error );
            } );
        }
    } );

    $( '#rtbcb-clear-report' ).on( 'click', function( e ) {
        e.preventDefault();
        $( '#rtbcb-report-preview' ).empty();
        $( '#rtbcb-report-sections, #rtbcb-report-actions, #rtbcb-report-summary' ).hide();
        $( '#rtbcb-report-sections .rtbcb-section-content' ).empty();
        latestReportText = '';
    } );

    function regenerateCompany() {
        var company = $( '#rtbcb-company-name' ).val().trim();
        if ( ! company ) {
            alert( rtbcbAdmin.strings.error );
            return;
        }
        var container = $( '#rtbcb-section-company_overview' );
        container.find( '.rtbcb-section-content' ).text( rtbcbAdmin.strings.generating );
        $.post( rtbcbAdmin.ajax_url, {
            action: 'rtbcb_test_company_overview',
            nonce: rtbcbAdmin.company_overview_nonce,
            company_name: company
        } ).done( function( resp ) {
            if ( resp.success ) {
                container.find( '.rtbcb-section-content' ).text( resp.data.overview );
                updateSectionMeta( 'company_overview', resp.data );
            } else {
                container.find( '.rtbcb-section-content' ).text( resp.data.message || rtbcbAdmin.strings.error );
            }
        } ).fail( function() {
            container.find( '.rtbcb-section-content' ).text( rtbcbAdmin.strings.error );
        } );
    }

    function regenerateTech() {
        var focusRaw = $( '#rtbcb-focus-areas' ).val().trim();
        var complexity = $( '#rtbcb-complexity' ).val();
        var focus = focusRaw.split( ',' ).map( function( s ) {
            return s.trim();
        } ).filter( Boolean );
        if ( ! focus.length ) {
            alert( rtbcbAdmin.strings.error );
            return;
        }
        var container = $( '#rtbcb-section-treasury_tech_overview' );
        container.find( '.rtbcb-section-content' ).text( rtbcbAdmin.strings.generating );
        $.post( rtbcbAdmin.ajax_url, {
            action: 'rtbcb_test_treasury_tech_overview',
            nonce: rtbcbAdmin.treasury_tech_overview_nonce,
            focus_areas: focus,
            complexity: complexity
        } ).done( function( resp ) {
            if ( resp.success ) {
                container.find( '.rtbcb-section-content' ).text( resp.data.overview );
                updateSectionMeta( 'treasury_tech_overview', resp.data );
            } else {
                container.find( '.rtbcb-section-content' ).text( resp.data.message || rtbcbAdmin.strings.error );
            }
        } ).fail( function() {
            container.find( '.rtbcb-section-content' ).text( rtbcbAdmin.strings.error );
        } );
    }

    function regenerateROI() {
        var container = $( '#rtbcb-section-roi' );
        container.find( '.rtbcb-section-content' ).text( rtbcbAdmin.strings.generating );
        $.post( rtbcbAdmin.ajax_url, {
            action: 'rtbcb_test_calculate_roi',
            nonce: rtbcbAdmin.roi_nonce
        } ).done( function( resp ) {
            if ( resp.success ) {
                container.find( '.rtbcb-section-content' ).text( JSON.stringify( resp.data.roi ) );
                updateSectionMeta( 'roi', resp.data );
            } else {
                container.find( '.rtbcb-section-content' ).text( resp.data.message || rtbcbAdmin.strings.error );
            }
        } ).fail( function() {
            container.find( '.rtbcb-section-content' ).text( rtbcbAdmin.strings.error );
        } );
    }

    $( '#rtbcb-export-pdf' ).on( 'click', function( e ) {
        e.preventDefault();
        var html = $( '#rtbcb-report-preview' ).html();
        if ( html ) {
            var w = window.open( '' );
            w.document.write( '<html><body>' + html + '</body></html>' );
            w.document.close();
            w.focus();
            w.print();
        }
    } );
} );
