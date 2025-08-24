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
                timeout: 180000,
                data: {
                    action: 'rtbcb_company_overview_simple',
                    company_name: companyName,
                    nonce: rtbcb_ajax.nonce,
                    include_prompt: true // Request to include prompt in response
                },
                beforeSend: function() {
                    generateBtn.prop('disabled', true).text( __( 'Connecting...', 'rtbcb' ) );

                    // Show initial progress with prompt preparation
                    resultsDiv.html('<div class="rtbcb-progress-container">' +
                        '<div class="rtbcb-progress-section">' +
                        '<h4>' + __( 'Preparing Analysis Request', 'rtbcb' ) + '</h4>' +
                        '<p>' + __( 'Building prompt for: ', 'rtbcb' ) + '<strong>' + companyName + '</strong></p>' +
                        '</div></div>');

                    progress = rtbcbTestUtils.startProgress(resultsDiv.find('.rtbcb-progress-container'), [
                        __( 'Connecting to AI service...', 'rtbcb' ),
                        __( 'Sending analysis request...', 'rtbcb' ),
                        __( 'AI processing company data...', 'rtbcb' ),
                        __( 'Generating treasury insights...', 'rtbcb' ),
                        __( 'Compiling recommendations...', 'rtbcb' ),
                        __( 'Finalizing analysis...', 'rtbcb' )
                    ]);
                },
                success: function(response) {
                    const duration = ( ( Date.now() - startTime ) / 1000 ).toFixed( 1 );
                    console.log(`Request completed in ${duration}s:`, response);

                    if (response.success) {
                        displaySimpleResults(response.data, duration);
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

        // Update displaySimpleResults to show the prompt and timing
        function displaySimpleResults(data, duration) {
            let html = '<div class="simple-results">';

            // Header with timing
            html += '<div class="results-header" style="background: #f0f0f1; padding: 15px; border-left: 4px solid #00a32a; margin-bottom: 20px;">';
            html += '<h3 style="margin: 0 0 10px 0;">' + __( 'Company Analysis Results', 'rtbcb' ) + '</h3>';
            html += '<p style="margin: 0;"><strong>' + __( 'Status:', 'rtbcb' ) + '</strong> ' + data.status + '</p>';
            if (duration) {
                html += '<p style="margin: 5px 0 0 0;"><strong>' + __( 'Processing Time:', 'rtbcb' ) + '</strong> ' + duration + 's</p>';
            }
            html += '</div>';

            // Show the prompt that was sent (if available)
            if (data.prompt_sent) {
                html += '<div class="prompt-display" style="margin-bottom: 20px;">';
                html += '<details style="border: 1px solid #ddd; border-radius: 4px; padding: 10px;">';
                html += '<summary style="cursor: pointer; font-weight: bold; padding: 5px 0;">' +
                    __( 'View AI Prompt Sent', 'rtbcb' ) + ' <small>(' + __( 'click to expand', 'rtbcb' ) + ')</small></summary>';
                html += '<div style="margin-top: 10px; background: #f9f9f9; padding: 15px; border-radius: 4px; overflow-x: auto;">';
                html += '<h4 style="margin-top: 0;">' + __( 'System Instructions:', 'rtbcb' ) + '</h4>';
                html += '<pre style="white-space: pre-wrap; font-size: 12px; line-height: 1.4;">' +
                    escapeHtml(data.prompt_sent.system || 'No system prompt') + '</pre>';
                html += '<h4>' + __( 'User Prompt:', 'rtbcb' ) + '</h4>';
                html += '<pre style="white-space: pre-wrap; font-size: 12px; line-height: 1.4;">' +
                    escapeHtml(data.prompt_sent.user || 'No user prompt') + '</pre>';
                html += '</div></details></div>';
            }

            // Analysis results
            if (data.simple_analysis) {
                html += '<div class="analysis-results">';
                html += '<h4>' + __( 'Company Analysis:', 'rtbcb' ) + '</h4>';
                html += '<div style="background: white; padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px;">';
                html += '<p style="line-height: 1.6;">' + data.simple_analysis.analysis + '</p>';
                html += '</div>';

                if (data.simple_analysis.recommendations && data.simple_analysis.recommendations.length > 0) {
                    html += '<h4>' + __( 'Treasury Technology Recommendations:', 'rtbcb' ) + '</h4>';
                    html += '<div style="background: white; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">';
                    html += '<ul style="margin: 0; padding-left: 20px;">';
                    data.simple_analysis.recommendations.forEach(function(rec) {
                        html += '<li style="margin-bottom: 8px; line-height: 1.5;">' + rec + '</li>';
                    });
                    html += '</ul></div>';
                }

                if (data.simple_analysis.references && data.simple_analysis.references.length > 0) {
                    html += '<h4 style="margin-top: 20px;">' + __( 'References:', 'rtbcb' ) + '</h4>';
                    html += '<div style="background: #f8f9fa; padding: 10px; border-radius: 4px;">';
                    html += '<ul style="margin: 0; padding-left: 20px; font-size: 12px;">';
                    data.simple_analysis.references.forEach(function(ref) {
                        html += '<li style="margin-bottom: 5px;"><a href="' + ref + '" target="_blank">' + ref + '</a></li>';
                    });
                    html += '</ul></div>';
                }
                html += '</div>';
            }

            // Debug information
            if (data.debug_info) {
                html += '<div class="debug-info" style="margin-top: 20px;">';
                html += '<details style="border: 1px solid #ccc; border-radius: 4px; padding: 10px; background: #f8f9fa;">';
                html += '<summary style="cursor: pointer; font-weight: bold; color: #666;">' +
                    __( 'Debug Information', 'rtbcb' ) + '</summary>';
                html += '<pre style="font-size: 11px; margin-top: 10px; white-space: pre-wrap;">' +
                    JSON.stringify(data.debug_info, null, 2) + '</pre>';
                html += '</details></div>';
            }

            html += '</div>';
            resultsDiv.html(html);
        }

        // Helper function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
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
