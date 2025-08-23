(function($) {
    'use strict';

    $(document).ready(function() {
        const form = $('#rtbcb-recommended-category-form');
        const resultsDiv = $('#rtbcb-category-recommendation-results');
        const generateBtn = $('#rtbcb-generate-recommendation');

        form.on('submit', function(e) {
            e.preventDefault();

            const companySize = $('#rtbcb-company-size').val();
            const complexity = $('#rtbcb-treasury-complexity').val();
            const budget = $('#rtbcb-budget-range').val();
            const timeline = $('#rtbcb-implementation-timeline').val();
            const painPoints = [];
            $('input[name="rtbcb-pain-points[]"]:checked').each(function() {
                painPoints.push($(this).val());
            });

            const data = {
                action: 'rtbcb_generate_category_recommendation',
                company_size: companySize,
                treasury_complexity: complexity,
                budget_range: budget,
                timeline: timeline,
                pain_points: painPoints,
                nonce: rtbcb_ajax.nonce
            };

            const start = performance.now();
            const original = rtbcbTestUtils.showLoading(generateBtn, 'Generating...');
            resultsDiv.html('<p>Generating recommendation...</p>');

            $.ajax({
                url: rtbcb_ajax.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        const r = response.data;
                        const container = $('<div class="notice notice-success" />');

                        $('<h2 />').text(r.recommended.name).appendTo(container);
                        $('<p />').text(r.recommended.description).appendTo(container);
                        $('<p />').html('<strong>Reasoning:</strong> ').append($('<span />').text(r.reasoning)).appendTo(container);

                        if (r.alternatives && r.alternatives.length) {
                            const altWrap = $('<div />');
                            altWrap.append('<strong>Alternatives:</strong>');
                            const altList = $('<ul />');
                            r.alternatives.forEach(function(alt) {
                                altList.append($('<li />').text(alt.name + ' (' + alt.score + ')'));
                            });
                            altWrap.append(altList);
                            container.append(altWrap);
                        }

                        $('<p />').html('<strong>Confidence:</strong> ' + r.confidence).appendTo(container);

                        if (r.implementation_roadmap) {
                            $('<p />').html('<strong>Roadmap:</strong> ').append($('<span />').text(r.implementation_roadmap)).appendTo(container);
                        }

                        if (r.success_factors) {
                            $('<p />').html('<strong>Success Factors:</strong> ').append($('<span />').text(r.success_factors)).appendTo(container);
                        }

                        const meta = rtbcbTestUtils.computeMeta(r.reasoning, start);
                        $('<p class="rtbcb-result-meta" />')
                            .text('Generated: ' + meta.generated + ' | Elapsed: ' + meta.elapsed + 's')
                            .appendTo(container);

                        resultsDiv.html(container);
                    } else {
                        rtbcbTestUtils.renderError(resultsDiv, response.data.message || 'Failed to generate recommendation', function() {
                            form.trigger('submit');
                        });
                    }
                },
                error: function() {
                    rtbcbTestUtils.renderError(resultsDiv, 'Request failed. Please try again.', function() {
                        form.trigger('submit');
                    });
                },
                complete: function() {
                    rtbcbTestUtils.hideLoading(generateBtn, original);
                }
            });
        });
    });
})(jQuery);

