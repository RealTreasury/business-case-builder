(function($){
    'use strict';

    const ReportTest = {
        init() {
            this.bindGenerate();
            this.bindSectionRegenerate();
            this.bindExport();
        },

        bindGenerate() {
            const form = $('#rtbcb-report-test-form');
            if (!form.length) { return; }
            const button = $('#rtbcb-generate-report');
            form.on('submit', async function(e){
                e.preventDefault();
                const company = $('#rtbcb-company-name').val().trim();
                if (!company) {
                    ReportTest.showError(rtbcbReportTest.strings.missing_company);
                    return;
                }
                const original = button.text();
                button.prop('disabled', true).text(rtbcbReportTest.strings.generating);
                ReportTest.clearError();
                try {
                    const formData = new FormData(form[0]);
                    formData.append('action', 'rtbcb_generate_complete_report');
                    formData.append('nonce', rtbcbReportTest.nonce);
                    const response = await fetch(rtbcbReportTest.ajax_url, { method: 'POST', body: formData });
                    if (!response.ok) { throw new Error(response.status); }
                    const data = await response.json();
                    if (data.success) {
                        ReportTest.renderReport(data.data);
                    } else {
                        ReportTest.showError(data.data?.message || rtbcbReportTest.strings.error, () => form.trigger('submit'));
                    }
                } catch(err){
                    ReportTest.showError(rtbcbReportTest.strings.error + ' ' + err.message, () => form.trigger('submit'));
                }
                button.prop('disabled', false).text(original);
            });
        },

        renderReport(data) {
            ReportTest.clearError();
            const iframe = $('#rtbcb-test-report-frame');
            if (iframe.length) { iframe.attr('srcdoc', data.html || ''); }
            if (data.download_url) {
                $('#rtbcb-export-html').attr('href', data.download_url).show();
            }
            $('#rtbcb-export-pdf').show();
            const words = data.word_counts && data.word_counts.combined ? data.word_counts.combined : 0;
            const elapsed = data.timestamps && data.timestamps.elapsed ? data.timestamps.elapsed.toFixed(2) : 0;
            const generated = data.timestamps && data.timestamps.end ? new Date(data.timestamps.end * 1000).toLocaleString() : '';
            $('#rtbcb-report-meta').text('Word count: ' + words + ' | Duration: ' + elapsed + 's | Generated: ' + generated);
            const sections = $('#rtbcb-report-sections').empty();
            if (data.sections) {
                Object.keys(data.sections).forEach(function(key){
                    const content = data.sections[key];
                    const section = $('<div class="rtbcb-section" />').attr('data-section', key);
                    section.append($('<h2 />').text(key.replace(/_/g, ' ')));
                    section.append($('<pre class="rtbcb-section-content" />').text(content));
                    const wc = data.word_counts && data.word_counts[key] ? data.word_counts[key] : content.trim().split(/\s+/).length;
                    section.append($('<p class="rtbcb-section-meta" />').text('Word count: ' + wc));
                    const regen = $('<button type="button" class="button rtbcb-regenerate" />')
                        .text(rtbcbAdmin.strings.regenerate || 'Regenerate')
                        .data('section', key);
                    section.append(regen);
                    sections.append(section);
                });
            }
        },

        showError(msg, retry){
            const box = $('#rtbcb-report-error');
            box.empty().show().text(msg);
            if (retry) {
                const btn = $('<button type="button" class="button" />').text(rtbcbReportTest.strings.retry);
                btn.on('click', retry);
                box.append(' ').append(btn);
            }
        },

        clearError(){
            $('#rtbcb-report-error').hide().empty();
        },

        bindSectionRegenerate(){
            $('#rtbcb-report-sections').on('click', '.rtbcb-regenerate', function(){
                const section = $(this).data('section');
                ReportTest.regenerateSection(section, $(this));
            });
        },

        async regenerateSection(section, button){
            const original = button.text();
            button.prop('disabled', true).text(rtbcbAdmin.strings.generating);
            try {
                const formData = new FormData();
                let action = '';
                switch(section){
                    case 'company_overview':
                        action = 'rtbcb_test_company_overview';
                        formData.append('company_name', $('#rtbcb-company-name').val());
                        formData.append('nonce', rtbcbAdmin.company_overview_nonce);
                        break;
                    case 'treasury_tech_overview':
                        action = 'rtbcb_test_treasury_tech_overview';
                        $('#rtbcb-focus-areas').val().split(',').map(s => s.trim()).forEach(f => formData.append('focus_areas[]', f));
                        formData.append('complexity', $('#rtbcb-complexity').val());
                        formData.append('nonce', rtbcbAdmin.treasury_tech_overview_nonce);
                        break;
                    default:
                        button.prop('disabled', false).text(original);
                        return;
                }
                formData.append('action', action);
                const response = await fetch(rtbcbAdmin.ajax_url, { method: 'POST', body: formData });
                if (!response.ok) { throw new Error(response.status); }
                const data = await response.json();
                if (data.success) {
                    const container = $('.rtbcb-section[data-section="' + section + '"]');
                    container.find('.rtbcb-section-content').text(data.data.overview || data.data.commentary || '');
                    const meta = 'Word count: ' + (data.data.word_count || 0) + ' | Generated: ' + (data.data.generated || '');
                    container.find('.rtbcb-section-meta').text(meta);
                } else {
                    alert(data.data?.message || rtbcbAdmin.strings.error);
                }
            } catch(err){
                alert(rtbcbAdmin.strings.error + ' ' + err.message);
            }
            button.prop('disabled', false).text(original);
        },

        bindExport(){
            $('#rtbcb-export-pdf').on('click', function(e){
                e.preventDefault();
                const iframe = document.getElementById('rtbcb-test-report-frame');
                if (iframe && iframe.contentWindow) {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                }
            });
        }
    };

    $(function(){ ReportTest.init(); });

})(jQuery);
