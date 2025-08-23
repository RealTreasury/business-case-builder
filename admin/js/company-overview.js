(function($) {
    'use strict';

    $(function() {
        const __ = window.wp && wp.i18n ? wp.i18n.__ : ( s ) => s;
        const generateBtn = $('#rtbcb-generate-company-overview');
        const resultsDiv = $('#rtbcb-company-overview-results');

        function sendRequest(companyName) {
            console.log('Starting simple company overview request');

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                timeout: 60000,
                data: {
                    action: 'rtbcb_company_overview_simple',
                    company_name: companyName,
                    nonce: rtbcb_ajax.nonce
                },
                beforeSend: function() {
                    generateBtn.prop('disabled', true).text( __( 'Testing Connection...', 'rtbcb' ) );
                },
                success: function(response) {
                    console.log('AJAX Success:', response);
                    if (response.success) {
                        displaySimpleResults(response.data);
                    } else {
                        showError( __( 'Request failed: ', 'rtbcb' ) + response.data.message );
                    }
                },
                error: function(xhr, textStatus, error) {
                    console.log('AJAX Error:', textStatus, error, 'Status:', xhr.status, 'Response:', xhr.responseText);
                    let message;
                    if (textStatus === 'timeout') {
                        message = __( 'The request timed out. Please try again.', 'rtbcb' );
                    } else if (xhr.status === 504) {
                        message = __( 'Connection failed: The server took too long to respond.', 'rtbcb' );
                    } else {
                        message = __( 'Connection failed: ', 'rtbcb' ) + textStatus + ' - ' + error;
                    }
                    showError(message);
                },
                complete: function() {
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

        function showError(message) {
            resultsDiv.html('<div class="error-message" style="color: red; padding: 10px; border: 1px solid red;">' + message + '</div>');
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
