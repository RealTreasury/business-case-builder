(function($) {
    'use strict';

    $(function() {
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
                    generateBtn.prop('disabled', true).text('Testing Connection...');
                },
                success: function(response) {
                    console.log('AJAX Success:', response);
                    if (response.success) {
                        displaySimpleResults(response.data);
                    } else {
                        showError('Request failed: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', status, error);
                    if (status === 'timeout') {
                        showError('The request timed out. Please try again.');
                    } else {
                        showError('Connection failed: ' + status + ' - ' + error);
                    }
                },
                complete: function() {
                    generateBtn.prop('disabled', false).text('Generate Overview');
                }
            });
        }

        function displaySimpleResults(data) {
            let html = '<div class="simple-results">';
            html += '<h3>Connection Test Results</h3>';
            html += '<p><strong>Status:</strong> ' + data.status + '</p>';
            html += '<p><strong>Message:</strong> ' + data.message + '</p>';

            if (data.simple_analysis) {
                html += '<h4>Sample Analysis:</h4>';
                html += '<p>' + data.simple_analysis.analysis + '</p>';

                if (data.simple_analysis.recommendations) {
                    html += '<h4>Sample Recommendations:</h4><ul>';
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
                alert('Please enter a company name.');
                return;
            }
            sendRequest(companyName);
        });
    });
})(jQuery);
