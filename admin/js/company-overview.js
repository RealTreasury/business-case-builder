(function($) {
    'use strict';

    $(function() {
        const button = $('#rtbcb-generate-company-overview');
        const resultsDiv = $('#rtbcb-company-overview-results');

        $('#rtbcb-clear-company-overview').on('click', function() {
            resultsDiv.html('');
            $('#rtbcb-test-company-name').val('');
        });

        button.on('click', function() {
            const companyName = $('#rtbcb-test-company-name').val().trim();
            const nonce = $('#rtbcb_test_company_overview_nonce').val();
            if (!companyName) {
                alert('Please enter a company name.');
                return;
            }
            const start = performance.now();
            rtbcbTestUtils.showLoading(button, (window.rtbcbAdmin && rtbcbAdmin.strings.generating) || 'Generating...');
            resultsDiv.html('<p>Generating company overview...</p>');
            $.ajax({
                url: rtbcb_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rtbcb_generate_company_overview',
                    company_name: companyName,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        rtbcbTestUtils.renderSuccess(resultsDiv, response.data.overview || '', start, function() {
                            button.trigger('click');
                        });
                    } else {
                        rtbcbTestUtils.renderError(resultsDiv, response.data.message || 'Failed to generate overview', function() {
                            button.trigger('click');
                        });
                    }
                },
                error: function() {
                    rtbcbTestUtils.renderError(resultsDiv, 'Request failed. Please try again.', function() {
                        button.trigger('click');
                    });
                },
                complete: function() {
                    rtbcbTestUtils.hideLoading(button);
                }
            });
        });
    });
})(jQuery);
