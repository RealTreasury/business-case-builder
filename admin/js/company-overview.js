(function($) {
    'use strict';

    $(document).ready(function() {
        const $generateBtn    = $('#rtbcb-generate-company-overview');
        const $regenerateBtn  = $('#rtbcb-regenerate-company-overview');
        const $clearBtn       = $('#rtbcb-clear-company-overview');
        const $copyBtn        = $('#rtbcb-copy-company-overview');
        const $resultsDiv     = $('#rtbcb-company-overview-results');
        const $metaDiv        = $('#rtbcb-company-overview-meta');
        const nonce           = $('#rtbcb_test_company_overview_nonce').val();

        function sendRequest() {
            const companyName = $('#rtbcb-test-company-name').val().trim();

            if (!companyName) {
                alert('Please enter a company name.');
                return;
            }

            $generateBtn.prop('disabled', true).text('Generating...');
            $regenerateBtn.prop('disabled', true).text('Generating...');
            $resultsDiv.html('<p>Generating company overview...</p>');
            $metaDiv.html('');

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
                        const overviewHtml = '<div class="notice notice-success"><p><strong>Company Overview:</strong></p><div class="rtbcb-overview" style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin-top: 10px;">' + data.overview + '</div></div>';
                        $resultsDiv.html(overviewHtml);
                        const metaHtml = '<p><strong>Word count:</strong> ' + data.word_count + '<br><strong>Generated at:</strong> ' + data.generated_at + '<br><strong>Elapsed time:</strong> ' + data.elapsed_time + 's</p>';
                        $metaDiv.html(metaHtml);
                        $regenerateBtn.show().prop('disabled', false).text('Regenerate');
                        $copyBtn.show();
                    } else {
                        showError(response.data.message || 'Failed to generate overview');
                    }
                },
                error: function() {
                    showError('Request failed. Please try again.');
                },
                complete: function() {
                    $generateBtn.prop('disabled', false).text('Generate Overview');
                }
            });
        }

        function showError(message) {
            $resultsDiv.html('<div class="notice notice-error"><p><strong>Error:</strong> ' + message + ' <a href="#" class="rtbcb-retry">Retry</a></p></div>');
            $metaDiv.html('');
            $regenerateBtn.show().prop('disabled', false).text('Regenerate');
        }

        $resultsDiv.on('click', '.rtbcb-retry', function(e) {
            e.preventDefault();
            sendRequest();
        });

        $generateBtn.on('click', sendRequest);
        $regenerateBtn.on('click', sendRequest);

        $clearBtn.on('click', function() {
            $resultsDiv.html('');
            $metaDiv.html('');
            $('#rtbcb-test-company-name').val('');
            $regenerateBtn.hide().prop('disabled', false).text('Regenerate');
            $copyBtn.hide();
        });

        $copyBtn.on('click', function() {
            const text = $resultsDiv.find('.rtbcb-overview').text();
            navigator.clipboard.writeText(text).then(function() {
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

