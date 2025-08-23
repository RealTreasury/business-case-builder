(function($) {
    'use strict';

    $(document).ready(function() {
        const efficiency = $('#rtbcb-test-efficiency');
        const efficiencyValue = $('#rtbcb-efficiency-value');
        const resultsDiv = $('#rtbcb-estimated-benefits-results');
        const generateBtn = $('#rtbcb-generate-benefits');

        efficiency.on('input change', function() {
            efficiencyValue.text($(this).val());
        });

        generateBtn.on('click', function() {
            const revenue = parseFloat($('#rtbcb-test-revenue').val());
            const staff = parseInt($('#rtbcb-test-staff-count').val(), 10);
            const eff = parseInt(efficiency.val(), 10);
            const category = $('#rtbcb-test-category').val();
            const nonce = $('#rtbcb_test_estimated_benefits_nonce').val();

            if (isNaN(revenue) || isNaN(staff) || !category) {
                alert('Please complete all fields.');
                return;
            }

            generateBtn.prop('disabled', true).text(rtbcb_estimated_benefits.strings.generating);
            resultsDiv.html('<p>' + rtbcb_estimated_benefits.strings.generating + '</p>');

            $.ajax({
                url: rtbcb_estimated_benefits.ajax_url,
                type: 'POST',
                data: {
                    action: 'rtbcb_test_estimated_benefits',
                    revenue: revenue,
                    staff_count: staff,
                    efficiency: eff,
                    category: category,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        const d = response.data;
                        let html = '<div class="notice notice-success"><p><strong>Results</strong></p><div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin-top: 10px;">';
                        html += 'Time Savings: ' + d.time_savings + '<br/>';
                        html += 'Cost Reductions: ' + d.cost_reductions + '<br/>';
                        html += 'Efficiency Gains: ' + d.efficiency_gains + '<br/>';
                        html += 'ROI: ' + d.roi + '<br/>';
                        html += 'Risk Mitigation: ' + d.risk_mitigation + '<br/>';
                        html += 'Productivity Gains: ' + d.productivity_gains + '<br/>';
                        html += 'Words: ' + d.words + ' | Time: ' + d.duration.toFixed(2) + 's<br/><br/>';
                        if (d.narrative) {
                            html += d.narrative;
                        }
                        html += '</div></div>';
                        resultsDiv.html(html);
                    } else {
                        resultsDiv.html('<div class="notice notice-error"><p><strong>Error:</strong> ' + (response.data || rtbcb_estimated_benefits.strings.error) + '</p></div>');
                    }
                },
                error: function() {
                    resultsDiv.html('<div class="notice notice-error"><p><strong>Error:</strong> ' + rtbcb_estimated_benefits.strings.error + '</p></div>');
                },
                complete: function() {
                    generateBtn.prop('disabled', false).text('Regenerate Estimate');
                }
            });
        });

        $('#rtbcb-clear-benefits').on('click', function() {
            $('#rtbcb-test-revenue').val('');
            $('#rtbcb-test-staff-count').val('');
            efficiency.val('5');
            efficiencyValue.text('5');
            $('#rtbcb-test-category').val('');
            generateBtn.text('Generate Estimate');
            resultsDiv.html('');
        });

        $('#rtbcb-copy-benefits').on('click', function() {
            const text = resultsDiv.text();
            if (!text) {
                return;
            }
            navigator.clipboard.writeText(text).then(function() {
                alert(rtbcb_estimated_benefits.strings.copied);
            });
        });
    });
})(jQuery);
