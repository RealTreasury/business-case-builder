(function($) {
    'use strict';

    $(document).ready(function() {
        const generateBtn = $('#rtbcb-generate-real-treasury-overview');
        const clearBtn = $('#rtbcb-clear-real-treasury-overview');
        const resultsDiv = $('#rtbcb-real-treasury-overview-results');
        const card = $('#rtbcb-real-treasury-overview-card');
        const includePortal = $('#rtbcb-include-portal');
        const categoriesSelect = $('#rtbcb-vendor-categories');
        const overrideCategories = $('#rtbcb-override-categories');
        const summaryDiv = $('#rtbcb-company-summary');
        const challengesList = $('#rtbcb-company-challenges');

        // Fetch stored company data
        $.ajax({
            url: rtbcb_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'rtbcb_get_company_data',
                nonce: rtbcb_ajax.nonce,
            },
            success: function(response) {
                if (response.success) {
                    summaryDiv.text(response.data.summary || '');
                    challengesList.empty();
                    (response.data.challenges || []).forEach(function(ch) {
                        challengesList.append($('<li />').text(ch));
                    });
                }
            }
        });

        overrideCategories.on('change', function() {
            if (overrideCategories.is(':checked')) {
                categoriesSelect.show();
            } else {
                categoriesSelect.hide().val([]);
            }
        });

        generateBtn.on('click', function() {
            const include = includePortal.is(':checked') ? 1 : 0;
            const categories = overrideCategories.is(':checked') ? (categoriesSelect.val() || []) : [];
            const nonce = rtbcb_ajax.nonce;
            const url = rtbcb_ajax.ajax_url;

            const start = performance.now();
            const original = rtbcbTestUtils.showLoading(generateBtn, 'Generating...');
            card.show();
            resultsDiv.html('<p>Generating overview...</p>');

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    action: 'rtbcb_test_real_treasury_overview',
                    include_portal: include,
                    categories: categories,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        const text = data.overview || '';
                        rtbcbTestUtils.renderSuccess(resultsDiv, text, start, {
                            word_count: data.word_count,
                            elapsed_time: data.elapsed,
                            generated_at: data.generated
                        });
                        $('#rtbcb-regenerate-real-treasury-overview').on('click', function(){
                            generateBtn.trigger('click');
                        });
                        $('#rtbcb-copy-real-treasury-overview').on('click', function(){
                            rtbcbTestUtils.copyToClipboard(text);
                        });
                    } else {
                        rtbcbTestUtils.renderError(resultsDiv, response.data.message || 'Failed to generate overview', function(){
                            generateBtn.trigger('click');
                        });
                    }
                },
                error: function() {
                    rtbcbTestUtils.renderError(resultsDiv, 'Request failed. Please try again.', function(){
                        generateBtn.trigger('click');
                    });
                },
                complete: function() {
                    rtbcbTestUtils.hideLoading(generateBtn, original);
                }
            });
        });

        clearBtn.on('click', function() {
            includePortal.prop('checked', false);
            overrideCategories.prop('checked', false);
            categoriesSelect.val([]).hide();
            resultsDiv.html('');
            card.hide();
        });
    });
})(jQuery);
