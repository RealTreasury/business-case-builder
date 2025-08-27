(function($) {
    'use strict';

    $(function() {
        const generateBtn = $('#rtbcb-generate-company-overview');
        const resultsDiv = $('#rtbcb-company-overview-results');
        const card = $('#rtbcb-company-overview-card');
        const metaDiv = $('#rtbcb-company-overview-meta');

        function sendRequest(companyName) {
            console.log('Starting simple company overview request');

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                timeout: 30000,
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
                    showError('Connection failed: ' + status + ' - ' + error);
                },
                complete: function() {
                    generateBtn.prop('disabled', false).text('Generate Overview');
                }
            });
        }

        function displaySimpleResults(data) {
            let html = '';
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

                if (data.simple_analysis.references) {
                    metaDiv.html('<p><strong>Sources:</strong> ' + data.simple_analysis.references.join(', ') + '</p>');
                }
            }

            resultsDiv.html(html);
            card.show();
        }

        function showError(message) {
            resultsDiv.html('<div class="error-message" style="color: red; padding: 10px; border: 1px solid red;">' + message + '</div>');
        }

        $('#rtbcb-regenerate-company-overview').on('click', function() {
            generateBtn.trigger('click');
        });

        $('#rtbcb-copy-company-overview').on('click', function() {
            var text = resultsDiv.text();
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text);
            }
        });

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
