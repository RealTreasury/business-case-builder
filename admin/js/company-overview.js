(function($) {
    'use strict';

    $(function() {
        const __ = window.wp && wp.i18n ? wp.i18n.__ : ( s ) => s;
        const generateBtn = $('#rtbcb-generate-company-overview');
        const resultsDiv = $('#rtbcb-company-overview-results');

        function sendRequest(companyName) {
            console.log('Starting company overview request for:', companyName);

            let progress;
            let startTime = Date.now();

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                timeout: 180000, // Increase to 3 minutes
                data: {
                    action: 'rtbcb_company_overview_simple',
                    company_name: companyName,
                    nonce: rtbcb_ajax.nonce
                },
                beforeSend: function() {
                    generateBtn.prop('disabled', true).text( __( 'Connecting...', 'rtbcb' ) );
                    progress = rtbcbTestUtils.startProgress(resultsDiv, [
                        __( 'Connecting to AI service...', 'rtbcb' ),
                        __( 'Analyzing company data...', 'rtbcb' ),
                        __( 'Generating treasury insights...', 'rtbcb' ),
                        __( 'Compiling recommendations...', 'rtbcb' ),
                        __( 'Finalizing analysis...', 'rtbcb' )
                    ]);
                },
                success: function(response) {
                    const duration = ( ( Date.now() - startTime ) / 1000 ).toFixed( 1 );
                    console.log(`Request completed in ${duration}s:`, response);

                    if (response.success) {
                        displaySimpleResults(response.data);
                    } else {
                        showError( __( 'Analysis failed: ', 'rtbcb' ) + ( response.data?.message || 'Unknown error' ) );
                    }
                },
                error: function(xhr, textStatus, error) {
                    const duration = ( ( Date.now() - startTime ) / 1000 ).toFixed( 1 );
                    console.log(`Request failed after ${duration}s:`, textStatus, error, 'Status:', xhr.status);

                    let message;
                    if (textStatus === 'timeout') {
                        message = __( 'The analysis is taking longer than expected. This may be due to high demand. Please try again in a few minutes.', 'rtbcb' );
                    } else if (xhr.status === 504) {
                        message = __( 'Gateway timeout - the server is temporarily overloaded. Please try again in a moment.', 'rtbcb' );
                    } else if (xhr.status === 502) {
                        message = __( 'Service temporarily unavailable. Please try again shortly.', 'rtbcb' );
                    } else if (xhr.status === 0) {
                        message = __( 'Connection lost. Please check your internet connection and try again.', 'rtbcb' );
                    } else {
                        message = __( 'Connection error: ', 'rtbcb' ) + textStatus + ( error ? ' - ' + error : '' );
                    }
                    showError(message);
                },
                complete: function() {
                    rtbcbTestUtils.stopProgress(progress);
                    generateBtn.prop('disabled', false).text( __( 'Generate Overview', 'rtbcb' ) );
                }
            });
        }

        function displaySimpleResults(data) {
            let html = '<div class="simple-results">';
            html += '<h3>' + __( 'Connection Test Results', 'rtbcb' ) + '</h3>';
            html += '<p><strong>' + __( 'Status:', 'rtbcb' ) + '</strong> ' + data.status + '</p>';
            html += '<p><strong>' + __( 'Message:', 'rtbcb' ) + '</strong> ' + data.message + '</p>';

            if (data.simple_analysis) {
                html += '<h4>' + __( 'Sample Analysis:', 'rtbcb' ) + '</h4>';
                html += '<p>' + data.simple_analysis.analysis + '</p>';

                if (data.simple_analysis.recommendations) {
                    html += '<h4>' + __( 'Sample Recommendations:', 'rtbcb' ) + '</h4><ul>';
                    data.simple_analysis.recommendations.forEach(function(rec) {
                        html += '<li>' + rec + '</li>';
                    });
                    html += '</ul>';
                }
            }

            html += '</div>';
            resultsDiv.html(html);
        }

        // Add retry functionality
        function showError(message) {
            const retryButton = '<button type="button" id="rtbcb-retry-overview" class="button button-secondary" style="margin-left: 10px;">' +
                __( 'Try Again', 'rtbcb' ) + '</button>';

            const errorHtml = '<div class="error-message" style="color: red; padding: 10px; border: 1px solid red; margin: 10px 0;">' +
                message + retryButton + '</div>';

            resultsDiv.html(errorHtml);

            $('#rtbcb-retry-overview').on('click', function() {
                const companyName = $('#rtbcb-test-company-name').val().trim();
                if (companyName) {
                    sendRequest(companyName);
                }
            });
        }

        generateBtn.on('click', function(e) {
            e.preventDefault();
            const companyName = $('#rtbcb-test-company-name').val().trim();
            if (!companyName) {
                alert( __( 'Please enter a company name.', 'rtbcb' ) );
                return;
            }
            sendRequest(companyName);
        });
    });
})(jQuery);
