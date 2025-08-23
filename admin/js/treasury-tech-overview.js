(function($) {
    'use strict';

    $(function() {
        const generateBtn = $('#rtbcb-generate-treasury-tech-overview');
        const clearBtn = $('#rtbcb-clear-treasury-tech-overview');
        const resultsDiv = $('#rtbcb-treasury-tech-overview-results');

        generateBtn.on('click', function() {
            const focusAreas = [];
            $('input[name="rtbcb_focus_areas[]"]:checked').each(function() {
                focusAreas.push($(this).val());
            });
            const complexity = $('#rtbcb-company-complexity').val();
            const nonce = $('#rtbcb_test_treasury_tech_overview_nonce').val();

            if (focusAreas.length === 0) {
                alert('Please select at least one focus area.');
                return;
            }

            const start = performance.now();
            rtbcbTestUtils.showLoading(generateBtn, (window.rtbcbAdmin && rtbcbAdmin.strings.generating) || 'Generating...');
            resultsDiv.html('<p>Generating overview...</p>');

            $.ajax({
                url: rtbcb_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rtbcb_test_treasury_tech_overview',
                    focus_areas: focusAreas,
                    complexity: complexity,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        rtbcbTestUtils.renderSuccess(resultsDiv, response.data.overview || '', start, function() {
                            generateBtn.trigger('click');
                        });
                    } else {
                        rtbcbTestUtils.renderError(resultsDiv, response.data.message || 'Failed to generate overview', function() {
                            generateBtn.trigger('click');
                        });
                    }
                },
                error: function() {
                    rtbcbTestUtils.renderError(resultsDiv, 'Request failed. Please try again.', function() {
                        generateBtn.trigger('click');
                    });
                },
                complete: function() {
                    rtbcbTestUtils.hideLoading(generateBtn);
                }
            });
        });

        clearBtn.on('click', function() {
            $('input[name="rtbcb_focus_areas[]"]').prop('checked', false);
            resultsDiv.html('');
        });
    });
})(jQuery);
