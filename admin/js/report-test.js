(function($){
    'use strict';

    $(function(){
        const output    = $('#rtbcb-report-output');
        const wordSpan  = $('#rtbcb-report-word-count');
        const timeSpan  = $('#rtbcb-report-generated');

        function updateSummary() {
            const text = [
                $('#rtbcb-section-commentary .rtbcb-section-content').text(),
                $('#rtbcb-section-company .rtbcb-section-content').text(),
                $('#rtbcb-section-tech .rtbcb-section-content').text()
            ].join(' ');
            const count = text.trim().split(/\s+/).filter(Boolean).length;
            wordSpan.text(count);
            timeSpan.text(new Date().toISOString().slice(0,19).replace('T',' '));
        }

        $('#rtbcb-generate-report').on('click', function(){
            const button      = $(this);
            const industry    = $('#rtbcb-report-industry').val().trim();
            const company     = $('#rtbcb-report-company').val().trim();
            const focusAreas  = $('#rtbcb-report-focus').val().split(',').map(s => s.trim()).filter(Boolean);
            const complexity  = $('#rtbcb-report-complexity').val().trim();

            if (!industry || !company || focusAreas.length === 0) {
                alert(rtbcbAdmin.strings.error);
                return;
            }

            button.prop('disabled', true).text(rtbcbAdmin.strings.generating);
            wordSpan.text('');
            timeSpan.text('');
            output.find('.notice').remove();
            $('#rtbcb-section-commentary .rtbcb-section-content').html('<p>' + rtbcbAdmin.strings.generating + '</p>');
            $('#rtbcb-section-company .rtbcb-section-content').html('<p>' + rtbcbAdmin.strings.generating + '</p>');
            $('#rtbcb-section-tech .rtbcb-section-content').html('<p>' + rtbcbAdmin.strings.generating + '</p>');
            output.show();

            $.post(rtbcbAdmin.ajax_url, {
                action: 'rtbcb_test_complete_report',
                industry: industry,
                company_name: company,
                focus_areas: focusAreas,
                complexity: complexity,
                nonce: rtbcbAdmin.complete_report_nonce
            }, function(response){
                if (response.success) {
                    $('#rtbcb-section-commentary .rtbcb-section-content').text(response.data.sections.industry_commentary);
                    $('#rtbcb-section-company .rtbcb-section-content').text(response.data.sections.company_overview);
                    $('#rtbcb-section-tech .rtbcb-section-content').text(response.data.sections.treasury_tech_overview);
                    wordSpan.text(response.data.word_count);
                    timeSpan.text(response.data.generated);
                } else {
                    output.prepend('<div class="notice notice-error"><p>' + (response.data.message || rtbcbAdmin.strings.error) + '</p><p><button class="button rtbcb-retry">' + rtbcbAdmin.strings.retry + '</button></p></div>');
                    output.find('.rtbcb-retry').on('click', function(){ $('#rtbcb-generate-report').trigger('click'); });
                }
            }, 'json').fail(function(){
                output.prepend('<div class="notice notice-error"><p>' + rtbcbAdmin.strings.error + '</p><p><button class="button rtbcb-retry">' + rtbcbAdmin.strings.retry + '</button></p></div>');
                output.find('.rtbcb-retry').on('click', function(){ $('#rtbcb-generate-report').trigger('click'); });
            }).always(function(){
                button.prop('disabled', false).text(rtbcbAdmin.strings.generate);
            });
        });

        function regen(section) {
            const map = {
                commentary: {
                    action: 'rtbcb_test_commentary',
                    data: { industry: $('#rtbcb-report-industry').val().trim(), nonce: rtbcbAdmin.commentary_nonce },
                    target: '#rtbcb-section-commentary .rtbcb-section-content'
                },
                company: {
                    action: 'rtbcb_test_company_overview',
                    data: { company_name: $('#rtbcb-report-company').val().trim(), nonce: rtbcbAdmin.company_overview_nonce },
                    target: '#rtbcb-section-company .rtbcb-section-content'
                },
                tech: {
                    action: 'rtbcb_test_treasury_tech_overview',
                    data: { focus_areas: $('#rtbcb-report-focus').val().split(',').map(s => s.trim()).filter(Boolean), complexity: $('#rtbcb-report-complexity').val().trim(), nonce: rtbcbAdmin.treasury_tech_overview_nonce },
                    target: '#rtbcb-section-tech .rtbcb-section-content'
                }
            };
            const cfg = map[section];
            const container = $(cfg.target);
            container.html('<p>' + rtbcbAdmin.strings.generating + '</p>');

            $.post(rtbcbAdmin.ajax_url, Object.assign({ action: cfg.action }, cfg.data), function(res){
                if (res.success) {
                    if (res.data.commentary) {
                        container.text(res.data.commentary);
                    } else {
                        container.text(res.data.overview);
                    }
                    updateSummary();
                } else {
                    container.html('<div class="notice notice-error"><p>' + (res.data.message || rtbcbAdmin.strings.error) + '</p><p><button class="button rtbcb-retry">' + rtbcbAdmin.strings.retry + '</button></p></div>');
                    container.find('.rtbcb-retry').on('click', function(){ regen(section); });
                }
            }, 'json').fail(function(){
                container.html('<div class="notice notice-error"><p>' + rtbcbAdmin.strings.error + '</p><p><button class="button rtbcb-retry">' + rtbcbAdmin.strings.retry + '</button></p></div>');
                container.find('.rtbcb-retry').on('click', function(){ regen(section); });
            });
        }

        $('.rtbcb-regenerate').on('click', function(){
            regen($(this).data('section'));
        });

        $('#rtbcb-export-html').on('click', function(){
            const content = '<html><body>' + output.html() + '</body></html>';
            const blob = new Blob([content], {type: 'text/html'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'report.html';
            a.click();
            URL.revokeObjectURL(url);
        });

        $('#rtbcb-export-pdf').on('click', function(){
            window.print();
        });
    });
})(jQuery);
