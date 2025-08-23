(function($) {
    'use strict';

    $(document).ready(function() {
        const $generateBtn    = $('#rtbcb-generate-company-overview');
        const $regenerateBtn  = $('#rtbcb-regenerate-company-overview');
        const $clearBtn       = $('#rtbcb-clear-company-overview');
        const $copyBtn        = $('#rtbcb-copy-company-overview');
        const $resultsDiv     = $('#rtbcb-company-overview-results');
        const nonce           = $('#rtbcb_test_company_overview_nonce').val();

        function sendRequest() {
            const companyName = $('#rtbcb-test-company-name').val().trim();

            if (!companyName) {
                alert('Please enter a company name.');
                return;
            }

            const start = performance.now();
            const originalGen = rtbcbTestUtils.showLoading($generateBtn, 'Generating...');
            const originalRegen = rtbcbTestUtils.showLoading($regenerateBtn, 'Generating...');
            $resultsDiv.html('<p>Generating company overview...</p>');

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
                        const data = response.data;
                        rtbcbTestUtils.renderSuccess($resultsDiv, data.overview || '', start, {
                            word_count: data.word_count,
                            generated_at: data.generated_at,
                            elapsed_time: data.elapsed_time
                        });
                        $regenerateBtn.show();
                        $copyBtn.show();
                    } else {
                        rtbcbTestUtils.renderError($resultsDiv, response.data.message || 'Failed to generate overview', sendRequest);
                        $regenerateBtn.show();
                    }
                },
                error: function() {
                    rtbcbTestUtils.renderError($resultsDiv, 'Request failed. Please try again.', sendRequest);
                    $regenerateBtn.show();
                },
                complete: function() {
                    rtbcbTestUtils.hideLoading($generateBtn, originalGen);
                    rtbcbTestUtils.hideLoading($regenerateBtn, originalRegen);
                }
            });
        }

        $generateBtn.on('click', sendRequest);
        $regenerateBtn.on('click', sendRequest);

        $clearBtn.on('click', function() {
            const originalClear = rtbcbTestUtils.showLoading($clearBtn, 'Clearing...');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'rtbcb_clear_current_company',
                    nonce: nonce
                },
                complete: function() {
                    rtbcbTestUtils.hideLoading($clearBtn, originalClear);
                    $resultsDiv.html('');
                    $('#rtbcb-test-company-name').val('');
                    $regenerateBtn.hide();
                    $copyBtn.hide();
                }
            });
        });

        $copyBtn.on('click', function() {
            const text = $resultsDiv.find('.rtbcb-result-text').text();
            rtbcbTestUtils.copyToClipboard(text).then(function() {
                $copyBtn.text('Copied!').prop('disabled', true);
                setTimeout(function() {
                    $copyBtn.text('Copy to Clipboard').prop('disabled', false);
                }, 2000);
            });
        });

        $regenerateBtn.hide();
        $copyBtn.hide();
    });
})(jQuery);

