(function($) {
    'use strict';

    $(document).ready(function() {
        const $generateBtn = $('#rtbcb-generate-category-recommendation');
        const $resultsDiv = $('#rtbcb-category-recommendation-results');

        function sendRequest() {
            const data = {
                action: 'rtbcb_generate_category_recommendation',
                nonce: rtbcb_ajax.nonce,
                company_size: $('#rtbcb-company-size').val(),
                treasury_complexity: $('#rtbcb-treasury-complexity').val(),
                budget_range: $('#rtbcb-budget-range').val(),
                timeline: $('#rtbcb-timeline').val(),
                pain_points: $('input[name="pain_points[]"]:checked').map(function() {
                    return $(this).val();
                }).get()
            };

            const original = rtbcbTestUtils.showLoading($generateBtn, 'Generating...');
            $resultsDiv.html('<p>Generating recommendation...</p>');

            $.ajax({
                url: rtbcb_ajax.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        const rec = response.data;
                        let html = '<h2>' + $('<div/>').text(rec.recommended.name || rec.recommended.key).html() + '</h2>';
                        if (rec.reasoning) {
                            html += '<p><strong>Reasoning:</strong> ' + $('<div/>').text(rec.reasoning).html() + '</p>';
                        }
                        if (rec.alternatives && rec.alternatives.length) {
                            html += '<p><strong>Alternatives:</strong></p><ul>';
                            rec.alternatives.forEach(function(alt) {
                                html += '<li>' + $('<div/>').text(alt.name || alt.key).html() + ' (' + alt.score + '%)</li>';
                            });
                            html += '</ul>';
                        }
                        if (rec.confidence) {
                            html += '<p><strong>Confidence:</strong> ' + rec.confidence + '%</p>';
                        }
                        if (rec.implementation_roadmap) {
                            html += '<p><strong>Roadmap:</strong> ' + $('<div/>').text(rec.implementation_roadmap).html() + '</p>';
                        }
                        if (rec.success_factors) {
                            html += '<p><strong>Success Factors:</strong> ' + $('<div/>').text(rec.success_factors).html() + '</p>';
                        }
                        $resultsDiv.html(html);
                    } else {
                        const message = (response.data && response.data.message) ? response.data.message : 'Failed to generate recommendation.';
                        rtbcbTestUtils.renderError($resultsDiv, message, sendRequest);
                    }
                },
                error: function() {
                    rtbcbTestUtils.renderError($resultsDiv, 'Request failed. Please try again.', sendRequest);
                },
                complete: function() {
                    rtbcbTestUtils.hideLoading($generateBtn, original);
                }
            });
        }

        $generateBtn.on('click', function(e) {
            e.preventDefault();
            sendRequest();
        });
    });
})(jQuery);
