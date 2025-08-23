(function($) {
    'use strict';

    $(function() {
        $('#rtbcb-generate-category').on('click', function() {
            const $button = $(this);
            const data = {
                action: 'rtbcb_generate_category_recommendation',
                nonce: rtbcb_ajax.nonce,
                company_size: $('#rtbcb-company-size').val(),
                treasury_complexity: $('#rtbcb-treasury-complexity').val(),
                pain_points: $('input[name="pain_points[]"]:checked').map(function() {
                    return $(this).val();
                }).get(),
                budget_range: $('#rtbcb-budget-range').val(),
                implementation_timeline: $('#rtbcb-implementation-timeline').val()
            };

            $button.prop('disabled', true);
            const $results = $('#rtbcb-category-results');
            $results.html('<p>Generating recommendation...</p>');

            $.post(rtbcb_ajax.ajax_url, data, function(response) {
                if (response.success) {
                    const rec = response.data;
                    $results.empty();
                    $('<h2>').text(rec.recommended_category || '').appendTo($results);
                    if (rec.reasoning) {
                        $('<p>').text('Reasoning: ' + rec.reasoning).appendTo($results);
                    }
                    if (rec.alternatives && rec.alternatives.length) {
                        const $altWrap = $('<div>').append($('<strong>').text('Alternatives:'));
                        const $list = $('<ul>');
                        rec.alternatives.forEach(function(item) {
                            $('<li>').text(item).appendTo($list);
                        });
                        $altWrap.append($list).appendTo($results);
                    }
                    if (rec.confidence) {
                        $('<p>').text('Confidence: ' + rec.confidence).appendTo($results);
                    }
                    if (rec.roadmap) {
                        $('<p>').text('Roadmap: ' + rec.roadmap).appendTo($results);
                    }
                    if (rec.success_factors) {
                        $('<p>').text('Success Factors: ' + rec.success_factors).appendTo($results);
                    }
                } else {
                    const msg = response.data && response.data.message ? response.data.message : 'An error occurred.';
                    $results.html('<p>' + msg + '</p>');
                }
            }).fail(function() {
                $results.html('<p>An error occurred.</p>');
            }).always(function() {
                $button.prop('disabled', false);
            });
        });
    });
})(jQuery);
