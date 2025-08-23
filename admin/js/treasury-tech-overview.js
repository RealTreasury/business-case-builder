(function($) {
    'use strict';

    $(document).ready(function() {
        const $generateBtn = $('#rtbcb-generate-treasury-tech-overview');
        const $clearBtn    = $('#rtbcb-clear-treasury-tech-overview');
        const $resultsDiv  = $('#rtbcb-treasury-tech-overview-results');
        const nonce        = $('#rtbcb_test_treasury_tech_overview_nonce').val();

        function sendRequest() {
            const focusAreas = [];
            $('input[name="rtbcb_focus_areas[]"]:checked').each(function() {
                focusAreas.push($(this).val());
            });
            const complexity = $('#rtbcb-company-complexity').val();

            if (focusAreas.length === 0) {
                alert('Please select at least one focus area.');
                return;
            }

            RTBCBTestUtils.showLoading($generateBtn);
            const start = performance.now();

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
                        const text = response.data.overview || '';
                        const overviewDiv = $('<div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin-top: 10px;" />').text(text);
                        RTBCBTestUtils.renderNotice($resultsDiv, 'success', '<strong>Overview:</strong>', null, overviewDiv);
                        const metrics = RTBCBTestUtils.computeMetrics(text, start);
                        const meta = $('<div />');
                        RTBCBTestUtils.renderMetrics(meta, metrics);
                        $resultsDiv.find('.notice').append(meta);
                        const actions = $('<p />');
                        const regen = $('<button type="button" class="button" />').text('Regenerate').on('click', sendRequest);
                        const copy = $('<button type="button" class="button" />').text('Copy').on('click', async function() {
                            const success = await RTBCBTestUtils.copy(text);
                            alert(success ? 'Copied to clipboard' : 'Copy failed');
                        });
                        actions.append(regen).append(' ').append(copy);
                        $resultsDiv.find('.notice').append(actions);
                    } else {
                        RTBCBTestUtils.renderNotice($resultsDiv, 'error', response.data.message || 'Failed to generate overview', sendRequest);
                    }
                },
                error: function() {
                    RTBCBTestUtils.renderNotice($resultsDiv, 'error', 'Request failed. Please try again.', sendRequest);
                },
                complete: function() {
                    RTBCBTestUtils.hideLoading($generateBtn);
                }
            });
        }

        $generateBtn.on('click', sendRequest);

        $clearBtn.on('click', function() {
            $('input[name="rtbcb_focus_areas[]"]').prop('checked', false);
            $resultsDiv.html('');
        });
    });
})(jQuery);
