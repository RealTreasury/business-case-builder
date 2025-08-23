(function($) {
    'use strict';

    $(document).ready(function() {
        $('#rtbcb-generate-category').on('click', function() {
            const button = $(this);
            const resultsDiv = $('#rtbcb-category-results');
            const data = {
                action: 'rtbcb_generate_category_recommendation',
                nonce: rtbcbCategory.nonce,
                company_size: $('#rtbcb-company-size').val(),
                treasury_complexity: $('#rtbcb-treasury-complexity').val(),
                pain_points: $('input[name="pain_points[]"]:checked').map(function() {
                    return $(this).val();
                }).get(),
                budget_range: $('#rtbcb-budget-range').val(),
                implementation_timeline: $('#rtbcb-implementation-timeline').val()
            };

            button.prop('disabled', true).text('Generating...');
            resultsDiv.html('<p>Generating recommendation...</p>');

            $.ajax({
                url: rtbcbCategory.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        const rec = response.data;
                        let html = '<div class="notice notice-success">';
                        html += '<p><strong>Recommended Category:</strong> ' + (rec.category_info && rec.category_info.name ? rec.category_info.name : rec.recommended) + '</p>';
                        if (rec.reasoning) {
                            html += '<p>' + rec.reasoning + '</p>';
                        }
                        if (rec.confidence) {
                            html += '<p><strong>Confidence:</strong> ' + rec.confidence + '%</p>';
                        }
                        if (rec.alternatives && rec.alternatives.length) {
                            html += '<p><strong>Alternatives:</strong></p><ul>';
                            rec.alternatives.forEach(function(alt) {
                                html += '<li>' + (alt.info && alt.info.name ? alt.info.name : alt.category) + '</li>';
                            });
                            html += '</ul>';
                        }
                        if (rec.roadmap) {
                            html += '<p><strong>Roadmap:</strong> ' + rec.roadmap + '</p>';
                        }
                        if (rec.success_factors && rec.success_factors.length) {
                            html += '<p><strong>Success Factors:</strong></p><ul>';
                            rec.success_factors.forEach(function(factor) {
                                html += '<li>' + factor + '</li>';
                            });
                            html += '</ul>';
                        }
                        html += '</div>';
                        resultsDiv.html(html);
                    } else {
                        resultsDiv.html('<div class="notice notice-error"><p><strong>Error:</strong> ' + (response.data.message || 'Failed to generate recommendation') + '</p></div>');
                    }
                },
                error: function() {
                    resultsDiv.html('<div class="notice notice-error"><p><strong>Error:</strong> Request failed. Please try again.</p></div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('Generate Recommendation');
                }
            });
        });
    });
})(jQuery);
