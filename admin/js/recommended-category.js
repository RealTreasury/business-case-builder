(function($) {
    'use strict';

    $(document).ready(function() {
        $('#rtbcb-generate-recommended-category').on('click', function() {
            var data = {
                action: 'rtbcb_generate_category_recommendation',
                nonce: rtbcb_recommended_category.nonce,
                company_size: $('#rtbcb-company-size').val(),
                treasury_complexity: $('#rtbcb-treasury-complexity').val(),
                pain_points: $('input[name="pain_points[]"]:checked').map(function() {
                    return $(this).val();
                }).get(),
                budget_range: $('#rtbcb-budget-range').val(),
                implementation_timeline: $('#rtbcb-implementation-timeline').val()
            };

            var resultsDiv = $('#rtbcb-recommended-category-results');
            resultsDiv.html('<p>Generating recommendation...</p>');

            $.post(rtbcb_recommended_category.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        var res = response.data;
                        var html = '<h2>' + res.recommended + '</h2>';
                        html += '<p>' + res.reasoning + '</p>';
                        if (res.alternatives && res.alternatives.length) {
                            html += '<p><strong>Alternatives:</strong> ' + res.alternatives.join(', ') + '</p>';
                        }
                        html += '<p><strong>Confidence:</strong> ' + res.confidence + '%</p>';
                        if (res.roadmap) {
                            html += '<p><strong>Roadmap:</strong> ' + res.roadmap + '</p>';
                        }
                        if (res.success_factors) {
                            html += '<p><strong>Success Factors:</strong> ' + res.success_factors + '</p>';
                        }
                        resultsDiv.html(html);
                    } else {
                        resultsDiv.html('<div class="notice notice-error"><p>' + (response.data && response.data.message ? response.data.message : 'An error occurred.') + '</p></div>');
                    }
                })
                .fail(function() {
                    resultsDiv.html('<div class="notice notice-error"><p>Request failed.</p></div>');
                });
        });
    });
})(jQuery);
