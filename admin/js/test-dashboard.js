(function($) {
    'use strict';

    function runTests() {
        const endpoints = [
            { action: 'rtbcb_test_company_overview', nonce: rtbcbAdmin.company_overview_nonce, label: 'Company Overview' },
            { action: 'rtbcb_test_treasury_tech_overview', nonce: rtbcbAdmin.treasury_tech_overview_nonce, label: 'Treasury Tech Overview' },
            { action: 'rtbcb_test_industry_overview', nonce: rtbcbAdmin.industry_overview_nonce, label: 'Industry Overview' }
        ];
        const results = [];
        let index = 0;

        function next() {
            if (index >= endpoints.length) {
                saveResults();
                return;
            }
            const ep = endpoints[index];
            $('#rtbcb-test-status').text('Testing ' + ep.label + '...');
            $.ajax({
                url: rtbcbAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: ep.action,
                    nonce: ep.nonce
                }
            }).done(function(response) {
                const message = response && response.data && response.data.message ? response.data.message : '';
                results.push({ section: ep.label, status: response.success ? 'success' : 'error', message: message });
                index++;
                next();
            }).fail(function() {
                results.push({ section: ep.label, status: 'error', message: 'Request failed' });
                index++;
                next();
            });
        }

        function saveResults() {
            $('#rtbcb-test-status').text('Saving results...');
            $.ajax({
                url: rtbcbTestDashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'rtbcb_save_test_results',
                    nonce: rtbcbTestDashboard.nonce,
                    results: JSON.stringify(results)
                }
            }).always(function() {
                updateSummary(results);
                $('#rtbcb-test-status').text('');
                $('#rtbcb-test-all-sections').prop('disabled', false).text('Test All Sections');
            });
        }

        function updateSummary(data) {
            const tbody = $('#rtbcb-test-results-summary tbody');
            tbody.empty();
            data.forEach(function(item) {
                const row = '<tr><td>' + item.section + '</td><td>' + item.status + '</td><td>' + item.message + '</td><td>' + new Date().toLocaleString() + '</td></tr>';
                tbody.append(row);
            });
        }

        next();
    }

    $(document).ready(function() {
        $('#rtbcb-test-all-sections').on('click', function() {
            const button = $(this);
            button.prop('disabled', true).text(rtbcbAdmin.strings.testing);
            runTests();
        });
    });
})(jQuery);
