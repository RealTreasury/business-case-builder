jQuery( function( $ ) {
    var latestReportText = '';
    var latestReportData = null;

    // Company name input uses a data attribute to avoid ID conflicts.
    function getCompanyName() {
        var input = $( '[data-company-name]' );
        return input.length ? input.val().trim() : '';
    }
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

    function assembleReportText() {
        if ( ! latestReportData || ! latestReportData.sections ) {
            latestReportText = '';
            return;
        }
        var parts = [];
        $.each( latestReportData.sections, function( id, section ) {
            if ( section.overview !== undefined ) {
                if ( 'string' === typeof section.overview ) {
                    parts.push( section.overview );
                } else {
                    parts.push( JSON.stringify( section.overview ) );
                }
            }
        } );
        latestReportText = parts.join( '\n\n' );
    }

    function generateReport() {
        var company = getCompanyName();
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
                latestReportData = d;
                $( '#rtbcb-report-preview' ).html( d.html || '' );
                $.each( d.sections, function( id, section ) {
                    $( '#rtbcb-section-' + id + ' .rtbcb-section-content' ).text( 'string' === typeof section.overview ? section.overview : JSON.stringify( section.overview ) );
                    updateSectionMeta( id, {
                        word_count: section.word_count,
                        elapsed: section.elapsed,
                        generated: new Date( ( d.generated || d.timestamps && d.timestamps.end || Date.now() / 1000 ) * 1000 ).toLocaleString()
                    } );
                } );
                assembleReportText();
                if ( d.word_count !== undefined ) {
                    $( '#rtbcb-summary-word-count' ).text( d.word_count );
                } else if ( d.word_counts && d.word_counts.combined !== undefined ) {
                    $( '#rtbcb-summary-word-count' ).text( d.word_counts.combined );
                }
                var generatedTime = new Date( ( d.generated || d.timestamps && d.timestamps.end || Date.now() / 1000 ) * 1000 ).toLocaleString();
                $( '#rtbcb-summary-generated' ).text( generatedTime );
                var elapsed = d.elapsed !== undefined ? d.elapsed : d.timestamps && d.timestamps.elapsed;
                if ( elapsed !== undefined ) {
                    $( '#rtbcb-summary-elapsed' ).text( ( elapsed.toFixed ? elapsed.toFixed( 2 ) : elapsed ) + 's' );
                }
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
        latestReportData = null;
    } );

    function regenerateCompany() {
        var company = getCompanyName();
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
                if ( latestReportData && latestReportData.sections ) {
                    latestReportData.sections.company_overview = resp.data;
                    assembleReportText();
                }
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
                if ( latestReportData && latestReportData.sections ) {
                    latestReportData.sections.treasury_tech_overview = resp.data;
                    assembleReportText();
                }
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
                var content = resp.data.overview !== undefined ? resp.data.overview : JSON.stringify( resp.data.roi );
                container.find( '.rtbcb-section-content' ).text( content );
                updateSectionMeta( 'roi', resp.data );
                if ( latestReportData && latestReportData.sections ) {
                    latestReportData.sections.roi = resp.data;
                    assembleReportText();
                }
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
