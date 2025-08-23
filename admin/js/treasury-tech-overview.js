(function($) {
    'use strict';

    $(document).ready(function() {
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
            const original = rtbcbTestUtils.showLoading(generateBtn, 'Generating...');
            resultsDiv.html('<p>Generating overview...</p>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'rtbcb_test_treasury_tech_overview',
                    focus_areas: focusAreas,
                    complexity: complexity,
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
                        const actions = $('<p />');
                        const regen = $('<button type="button" class="button" />').text('Regenerate');
                        const copy = $('<button type="button" class="button" />').text('Copy');
                        const clear = $('<button type="button" class="button" />').text('Clear');
                        regen.on('click', function() {
                            generateBtn.trigger('click');
                        });
                        copy.on('click', function() {
                            rtbcbTestUtils.copyToClipboard(text).then(function(){
                                alert('Copied to clipboard');
                            }).catch(function(err){
                                alert('Copy failed: ' + err.message);
                            });
                        });
                        clear.on('click', function() {
                            clearBtn.trigger('click');
                        });
                        actions.append(regen).append(' ').append(copy).append(' ').append(clear);
                        resultsDiv.find('.notice').append(actions);
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
            $('input[name="rtbcb_focus_areas[]"]').prop('checked', false);
            resultsDiv.html('');
        });
    });
})(jQuery);
