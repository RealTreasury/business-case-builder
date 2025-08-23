(function($) {
    'use strict';

    $(function() {
        $('#rtbcb-test-all').on('click', function() {
            const button = $(this);
            button.prop('disabled', true).text(rtbcbAdmin.strings.testing);

            const sections = [
                {
                    action: 'rtbcb_test_company_overview',
                    nonce: rtbcbAdmin.company_overview_nonce,
                    label: 'Company Overview'
                },
                {
                    action: 'rtbcb_test_treasury_tech_overview',
                    nonce: rtbcbAdmin.treasury_tech_overview_nonce,
                    label: 'Treasury Tech Overview'
                },
                {
                    action: 'rtbcb_generate_report_preview',
                    nonce: rtbcbAdmin.report_preview_nonce,
                    label: 'Report Preview'
                }
            ];

            const resultsTable = $('#rtbcb-test-summary tbody');

            function addResult(section, status, message) {
                const time = new Date().toLocaleString();
                const row = '<tr><td>' + time + '</td><td>' + section + '</td><td>' + status + '</td><td>' + message + '</td></tr>';
                resultsTable.prepend(row);
            }

            function saveResult(section, status, message) {
                $.post(rtbcbAdmin.ajax_url, {
                    action: 'rtbcb_save_test_result',
                    nonce: rtbcbAdmin.nonce,
                    section: section,
                    status: status,
                    message: message
                });
            }

            function runSection(index, attempt) {
                attempt = attempt || 1;
                if (index >= sections.length) {
                    button.prop('disabled', false).text('Test All Sections');
                    return;
                }
                const sec = sections[index];

                $.ajax({
                    url: rtbcbAdmin.ajax_url,
                    type: 'POST',
                    data: {
                        action: sec.action,
                        nonce: sec.nonce
                    },
                    success: function(response) {
                        const status = response.success ? 'success' : 'error';
                        const message = response.success ? '' : (response.data && response.data.message ? response.data.message : '');
                        addResult(sec.label, status, message);
                        saveResult(sec.label, status, message);
                        runSection(index + 1, 1);
                    },
                    error: function() {
                        if (attempt < 2) {
                            runSection(index, attempt + 1);
                        } else {
                            const status = 'error';
                            const message = 'Request failed';
                            addResult(sec.label, status, message);
                            saveResult(sec.label, status, message);
                            runSection(index + 1, 1);
                        }
                    }
                });
            }

            runSection(0, 1);
        });
    });
})(jQuery);
