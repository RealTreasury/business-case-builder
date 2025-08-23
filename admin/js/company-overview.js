(function($) {
    'use strict';

    $(document).ready(function() {
        const $generateBtn   = $('#rtbcb-generate-company-overview');
        const $regenerateBtn = $('#rtbcb-regenerate-company-overview');
        const $clearBtn      = $('#rtbcb-clear-company-overview');
        const $copyBtn       = $('#rtbcb-copy-company-overview');
        const $resultsDiv    = $('#rtbcb-company-overview-results');
        const $metaDiv       = $('#rtbcb-company-overview-meta');
        const nonce          = $('#rtbcb_test_company_overview_nonce').val();

        function sendRequest() {
            const companyName = $('#rtbcb-test-company-name').val().trim();
            if (!companyName) {
                alert('Please enter a company name.');
                return;
            }

            RTBCBTestUtils.showLoading($generateBtn);
            RTBCBTestUtils.showLoading($regenerateBtn);
            $copyBtn.hide();
            const start = performance.now();

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
                        const text = response.data.overview || '';
                        const overviewDiv = $('<div class="rtbcb-overview" style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin-top: 10px;" />').text(text);
                        RTBCBTestUtils.renderNotice($resultsDiv, 'success', '<strong>Company Overview:</strong>', null, overviewDiv);
                        const metrics = RTBCBTestUtils.computeMetrics(text, start);
                        RTBCBTestUtils.renderMetrics($metaDiv, metrics);
                        $regenerateBtn.show();
                        $copyBtn.show();
                    } else {
                        RTBCBTestUtils.renderNotice($resultsDiv, 'error', response.data.message || 'Failed to generate overview', sendRequest);
                        $metaDiv.html('');
                        $regenerateBtn.show();
                    }
                },
                error: function() {
                    RTBCBTestUtils.renderNotice($resultsDiv, 'error', 'Request failed. Please try again.', sendRequest);
                    $metaDiv.html('');
                    $regenerateBtn.show();
                },
                complete: function() {
                    RTBCBTestUtils.hideLoading($generateBtn);
                    RTBCBTestUtils.hideLoading($regenerateBtn);
                }
            });
        }

        $generateBtn.on('click', sendRequest);
        $regenerateBtn.on('click', sendRequest);

        $clearBtn.on('click', function() {
            $resultsDiv.html('');
            $metaDiv.html('');
            $('#rtbcb-test-company-name').val('');
            $regenerateBtn.hide();
            $copyBtn.hide();
        });

        $copyBtn.on('click', async function() {
            const text = $resultsDiv.find('.rtbcb-overview').text();
            const success = await RTBCBTestUtils.copy(text);
            if (success) {
                $copyBtn.text('Copied!').prop('disabled', true);
                setTimeout(function() {
                    $copyBtn.text('Copy to Clipboard').prop('disabled', false);
                }, 2000);
            } else {
                alert('Copy failed');
            }
        });

        $regenerateBtn.hide();
        $copyBtn.hide();
    });
})(jQuery);
