(function($) {
    'use strict';

    $(function() {
        const $btn = $('#rtbcb-generate-recommended-category');
        const $results = $('#rtbcb-recommended-category-results');
        const nonce = rtbcb_ajax.nonce;

        $btn.on('click', function() {
            const data = {
                action: 'rtbcb_generate_category_recommendation',
                nonce: nonce,
                company_size: $('#rtbcb-company-size').val(),
                treasury_complexity: $('#rtbcb-treasury-complexity').val(),
                pain_points: $('.rtbcb-pain-point:checked').map(function() { return $(this).val(); }).get(),
                budget_range: $('#rtbcb-budget-range').val(),
                implementation_timeline: $('#rtbcb-implementation-timeline').val()
            };

            const original = rtbcbTestUtils.showLoading($btn, 'Generating...');
            $results.html('<p>Generating recommendation...</p>');

            $.ajax({
                url: rtbcb_ajax.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        const rec = response.data;
                        let html = '<h2>' + rtbcbTestUtils.escapeHtml(rec.category_info.name || '') + '</h2>';
                        html += '<p>' + rtbcbTestUtils.escapeHtml(rec.reasoning || '') + '</p>';
                        if (rec.confidence !== undefined) {
                            html += '<p><strong>Confidence:</strong> ' + rtbcbTestUtils.escapeHtml(String(rec.confidence)) + '%</p>';
                        }
                        if (Array.isArray(rec.alternatives) && rec.alternatives.length) {
                            html += '<h3>Alternatives</h3><ul>';
                            rec.alternatives.forEach(function(alt) {
                                html += '<li>' + rtbcbTestUtils.escapeHtml(alt.info.name) + ' (' + alt.score + ')</li>';
                            });
                            html += '</ul>';
                        }
                        if (Array.isArray(rec.roadmap)) {
                            html += '<h3>Roadmap</h3><ol>';
                            rec.roadmap.forEach(function(step) {
                                html += '<li>' + rtbcbTestUtils.escapeHtml(step) + '</li>';
                            });
                            html += '</ol>';
                        }
                        if (Array.isArray(rec.success_factors)) {
                            html += '<h3>Success Factors</h3><ul>';
                            rec.success_factors.forEach(function(factor) {
                                html += '<li>' + rtbcbTestUtils.escapeHtml(factor) + '</li>';
                            });
                            html += '</ul>';
                        }
                        $results.html(html);
                    } else {
                        rtbcbTestUtils.renderError($results, response.data.message || 'Failed to generate recommendation');
                    }
                },
                error: function() {
                    rtbcbTestUtils.renderError($results, 'Request failed. Please try again.');
                },
                complete: function() {
                    rtbcbTestUtils.hideLoading($btn, original);
                }
            });
        });
    });
})(jQuery);
