(function($) {
    'use strict';

    $(function() {
        const $generateBtn = $('#rtbcb-generate-complete-report');
        const $preview     = $('#rtbcb-report-preview');
        const $meta        = $('#rtbcb-report-meta');

        function getSampleInputs() {
            if (!rtbcbAdmin.sampleForms || !rtbcbAdmin.sampleForms.default) {
                return null;
            }
            return rtbcbAdmin.sampleForms.default;
        }

        function showError(message) {
            $preview.html('<div class="notice notice-error"><p><strong>Error:</strong> ' + message + ' <a href="#" class="rtbcb-retry">Retry</a></p></div>');
            $meta.html('');
        }

        function attachSectionHandlers(inputs) {
            $preview.find('.rtbcb-section').each(function() {
                const section = $(this).data('section');
                const $btn    = $('<button type="button" class="button rtbcb-regenerate-section" />').text(rtbcbAdmin.strings.regenerate).data('section', section);
                $(this).append($btn);
            });

            $preview.off('click.rtbcb').on('click.rtbcb', '.rtbcb-regenerate-section', function(e) {
                e.preventDefault();
                const section   = $(this).data('section');
                const container = $(this).closest('.rtbcb-section');
                regenerateSection(section, container, inputs);
            });

            $preview.on('click', '.rtbcb-retry-section', function(e) {
                e.preventDefault();
                const container = $(this).closest('.rtbcb-section');
                const section   = container.data('section');
                regenerateSection(section, container, inputs);
            });
        }

        function regenerateSection(section, container, inputs) {
            const map = {
                'company_overview': {
                    action: 'rtbcb_test_company_overview',
                    data: {
                        company_name: inputs.company_name || '',
                        nonce: rtbcbAdmin.company_overview_nonce
                    }
                },
                'industry_overview': {
                    action: 'rtbcb_test_industry_overview',
                    data: {
                        industry: inputs.industry || '',
                        company_size: inputs.company_size || '',
                        nonce: rtbcbAdmin.industry_overview_nonce
                    }
                },
                'treasury_tech_overview': {
                    action: 'rtbcb_test_treasury_tech_overview',
                    data: {
                        focus_areas: inputs.focus_areas || [],
                        complexity: inputs.complexity || '',
                        nonce: rtbcbAdmin.treasury_tech_overview_nonce
                    }
                }
            };

            const config = map[section];
            if (!config) {
                return;
            }

            container.html('<p>' + rtbcbAdmin.strings.generating + '</p>');

            $.ajax({
                url: rtbcbAdmin.ajax_url,
                type: 'POST',
                data: $.extend({ action: config.action }, config.data),
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        container.html('<div class="rtbcb-section-content">' + data.overview + '</div>');
                    } else {
                        container.html('<div class="notice notice-error"><p>' + (response.data?.message || rtbcbAdmin.strings.error) + ' <a href="#" class="rtbcb-retry-section">Retry</a></p></div>');
                    }
                },
                error: function() {
                    container.html('<div class="notice notice-error"><p>' + rtbcbAdmin.strings.error + ' <a href="#" class="rtbcb-retry-section">Retry</a></p></div>');
                }
            });
        }

        $preview.on('click', '.rtbcb-retry', function(e) {
            e.preventDefault();
            $generateBtn.trigger('click');
        });

        $generateBtn.on('click', function(e) {
            e.preventDefault();
            const inputs = getSampleInputs();
            if (!inputs) {
                alert(rtbcbAdmin.strings.error);
                return;
            }
            const original = $generateBtn.text();
            $generateBtn.prop('disabled', true).text(rtbcbAdmin.strings.generating);
            $preview.html('<p>' + rtbcbAdmin.strings.generating + '</p>');
            $meta.html('');

            $.ajax({
                url: rtbcbAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'rtbcb_test_generate_complete_report',
                    nonce: rtbcbAdmin.complete_report_nonce,
                    inputs: JSON.stringify(inputs)
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        $preview.html(data.html);
                        $meta.text('Word count: ' + data.word_count + ' | Generated: ' + data.generated);
                        attachSectionHandlers(inputs);
                    } else {
                        showError(response.data?.message || rtbcbAdmin.strings.error);
                    }
                },
                error: function() {
                    showError(rtbcbAdmin.strings.error);
                },
                complete: function() {
                    $generateBtn.prop('disabled', false).text(original);
                }
            });
        });

        $('#rtbcb-export-report-html').on('click', function(e) {
            e.preventDefault();
            const html = '<html><body>' + $preview.html() + '</body></html>';
            const blob = new Blob([html], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'report.html';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });

        $('#rtbcb-export-report-pdf').on('click', function(e) {
            e.preventDefault();
            const content = '<html><head><title>Report</title></head><body>' + $preview.html() + '</body></html>';
            const win = window.open('', '', 'width=900,height=650');
            win.document.write(content);
            win.document.close();
            win.focus();
            win.print();
            win.close();
        });
    });
})(jQuery);

