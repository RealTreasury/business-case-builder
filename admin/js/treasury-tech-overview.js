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

            generateBtn.prop('disabled', true).text('Generating...');
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
                        const container = $('<div class="notice notice-success" />');
                        container.append('<p><strong>Overview:</strong></p>');
                        container.append('<div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin-top: 10px;">' + text + '</div>');
                        container.append('<p>Word count: ' + data.word_count + ' | Time: ' + data.elapsed + 's | Generated: ' + data.generated + '</p>');
                        const actions = $('<p />');
                        const regen = $('<button type="button" class="button" />').text('Regenerate');
                        const copy = $('<button type="button" class="button" />').text('Copy');
                        const clear = $('<button type="button" class="button" />').text('Clear');
                        regen.on('click', function() {
                            generateBtn.trigger('click');
                        });
                        copy.on('click', async function() {
                            try {
                                await navigator.clipboard.writeText(text);
                                alert('Copied to clipboard');
                            } catch (err) {
                                alert('Copy failed: ' + err.message);
                            }
                        });
                        clear.on('click', function() {
                            clearBtn.trigger('click');
                        });
                        actions.append(regen).append(' ').append(copy).append(' ').append(clear);
                        container.append(actions);
                        resultsDiv.html(container);
                    } else {
                        const container = $('<div class="notice notice-error" />');
                        container.append('<p><strong>Error:</strong> ' + (response.data.message || 'Failed to generate overview') + '</p>');
                        const retry = $('<button type="button" class="button" />').text('Retry');
                        retry.on('click', function() {
                            generateBtn.trigger('click');
                        });
                        container.append(retry);
                        resultsDiv.html(container);
                    }
                },
                error: function() {
                    const container = $('<div class="notice notice-error" />');
                    container.append('<p><strong>Error:</strong> Request failed. Please try again.</p>');
                    const retry = $('<button type="button" class="button" />').text('Retry');
                    retry.on('click', function() {
                        generateBtn.trigger('click');
                    });
                    container.append(retry);
                    resultsDiv.html(container);
                },
                complete: function() {
                    generateBtn.prop('disabled', false).text('Generate Overview');
                }
            });
        });

        clearBtn.on('click', function() {
            $('input[name="rtbcb_focus_areas[]"]').prop('checked', false);
            resultsDiv.html('');
        });
    });
})(jQuery);
