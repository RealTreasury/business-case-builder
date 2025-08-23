(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle Generate Overview button click
        $('#rtbcb-generate-company-overview').on('click', function() {
            const button = $(this);
            const companyName = $('#rtbcb-test-company-name').val().trim();
            const resultsDiv = $('#rtbcb-company-overview-results');
            const nonce = $('#rtbcb_test_company_overview_nonce').val();

            // Validate input
            if (!companyName) {
                alert('Please enter a company name.');
                return;
            }

            // Show loading state
            button.prop('disabled', true).text('Generating...');
            resultsDiv.html('<p>Generating company overview...</p>');

            // Make AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'rtbcb_generate_company_overview',
                    company_name: companyName,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        resultsDiv.html('<div class="notice notice-success"><p><strong>Company Overview:</strong></p><div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin-top: 10px;">' + response.data.overview + '</div></div>');
                    } else {
                        resultsDiv.html('<div class="notice notice-error"><p><strong>Error:</strong> ' + (response.data.message || 'Failed to generate overview') + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    resultsDiv.html('<div class="notice notice-error"><p><strong>Error:</strong> Request failed. Please try again.</p></div>');
                    console.error('AJAX Error:', error);
                },
                complete: function() {
                    // Restore button state
                    button.prop('disabled', false).text('Generate Overview');
                }
            });
        });

        // Handle Clear Results button click
        $('#rtbcb-clear-company-overview').on('click', function() {
            $('#rtbcb-company-overview-results').html('');
            $('#rtbcb-test-company-name').val('');
        });
    });
})(jQuery);
