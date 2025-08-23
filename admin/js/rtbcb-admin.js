(function($){
    'use strict';

    const RTBCBAdmin = {
        init() {
            this.bindDashboardActions();
            this.bindExportButtons();
            this.initLeadsManager();
            this.bindDiagnosticsButton();
            this.bindReportPreview();
            this.bindSampleReport();
            this.bindSyncLocal();
            this.bindCommentaryTest();
            this.bindCompanyOverviewTest();
            this.bindTestDashboard();
        },

        bindDashboardActions() {
            $('#rtbcb-test-api').on('click', this.testApiConnection);
            $('#rtbcb-rebuild-index').on('click', this.rebuildIndex);
            $('#rtbcb-export-data').on('click', this.exportLeads);
        },

        bindExportButtons() {
            $('#rtbcb-export-leads').on('click', this.exportLeads);
        },

        bindDiagnosticsButton() {
            $('#rtbcb-run-tests').on('click', this.runDiagnostics);
        },

        bindSyncLocal() {
            $('#rtbcb-sync-local').on('click', this.syncToLocal);
        },

        bindCommentaryTest() {
            if (!rtbcbAdmin || rtbcbAdmin.page !== 'rtbcb-calculations') { return; }
            const button = $('#rtbcb-generate-commentary');
            if (!button.length) { return; }
            const results = $('#rtbcb-commentary-results');
            button.on('click', async function (e) {
                e.preventDefault();
                const industry = $('#rtbcb-commentary-industry').val();
                const nonce = rtbcbAdmin.company_overview_nonce;
                const original = button.text();
                button.prop('disabled', true).text(rtbcbAdmin.strings.generating);
                try {
                    const formData = new FormData();
                    formData.append('action', 'rtbcb_test_company_overview');
                    formData.append('industry', industry);
                    formData.append('nonce', nonce);
                    const response = await fetch(rtbcbAdmin.ajax_url, { method: 'POST', body: formData });
                    if (!response.ok) {
                        throw new Error(`Server responded ${response.status}`);
                    }
                    const data = await response.json();
                    if (data.success) {
                        const overview = data.data.overview || '';
                        results.text(overview);
                        if (navigator.clipboard) {
                            try {
                                await navigator.clipboard.writeText(overview);
                                alert(rtbcbAdmin.strings.copied);
                            } catch (clipErr) {
                                // Ignore clipboard errors.
                            }
                        }
                    } else {
                        alert(data.data?.message || rtbcbAdmin.strings.error);
                    }
                } catch (err) {
                    alert(`${rtbcbAdmin.strings.error} ${err.message}`);
                }
                button.prop('disabled', false).text(original);
            });
        },

        bindCompanyOverviewTest() {
            if (!rtbcbAdmin || rtbcbAdmin.page !== 'rtbcb-test-company-overview') { return; }
            const form = $('#rtbcb-company-overview-form');
            if (!form.length) { return; }
            const results = $('#rtbcb-company-overview-results');
            const clearBtn = $('#rtbcb-clear-results');
            const submitBtn = form.find('button[type="submit"]');
            const submitHandler = async function(e) {
                e.preventDefault();
                const original = submitBtn.text();
                submitBtn.prop('disabled', true).text(rtbcbAdmin.strings.processing);
                const company = $('#rtbcb-company-name').val();
                const nonce = form.find('[name="nonce"]').val();
                const start = performance.now();
                try {
                    const formData = new FormData();
                    formData.append('action', 'rtbcb_test_company_overview');
                    formData.append('company', company);
                    formData.append('nonce', nonce);
                    const response = await fetch(rtbcbAdmin.ajax_url, { method: 'POST', body: formData });
                    if (!response.ok) {
                        throw new Error(`Server responded ${response.status}`);
                    }
                    const data = await response.json();
                    if (data.success) {
                        const text = data.data?.overview || '';
                        const wordCount = text.trim() ? text.trim().split(/\s+/).length : 0;
                        const duration = ((performance.now() - start) / 1000).toFixed(2);
                        const timestamp = new Date().toLocaleTimeString();
                        const container = $('<div />');
                        container.append($('<p />').text(text));
                        container.append($('<p />').text('Word count: ' + wordCount));
                        container.append($('<p />').text('Duration: ' + duration + 's'));
                        container.append($('<p />').text('Timestamp: ' + timestamp));
                        const actions = $('<p />');
                        const regen = $('<button type="button" class="button" />').text('Regenerate');
                        const copy = $('<button type="button" class="button" />').text('Copy Text');
                        regen.on('click', function(){ form.trigger('submit'); });
                        copy.on('click', async function(){
                            try {
                                await navigator.clipboard.writeText(text);
                                alert(rtbcbAdmin.strings.copied);
                            } catch (err) {
                                alert(rtbcbAdmin.strings.error + ' ' + err.message);
                            }
                        });
                        actions.append(regen).append(' ').append(copy);
                        container.append(actions);
                        results.html(container);
                    } else {
                        const message = data.data?.message || rtbcbAdmin.strings.error;
                        results.html('<div class="notice notice-error"><p>' + message + '</p></div>');
                    }
                } catch (err) {
                    results.html('<div class="notice notice-error"><p>' + rtbcbAdmin.strings.error + ' ' + err.message + '</p></div>');
                }
                submitBtn.prop('disabled', false).text(original);
            };
            form.on('submit', submitHandler);
            if (clearBtn.length) {
                clearBtn.on('click', function(){ results.empty(); });
            }
        },

        bindTestDashboard() {
            if (!rtbcbAdmin || rtbcbAdmin.page !== 'rtbcb-test-dashboard') { return; }
            const runBtn = $('#rtbcb-test-all');
            const clearBtn = $('#rtbcb-clear-all');
            const copyBtn = $('#rtbcb-copy-report');
            const results = $('#rtbcb-test-dashboard-results');
            let lastReport = '';

            clearBtn.on('click', function(){ results.empty(); lastReport = ''; });

            copyBtn.on('click', async function(){
                if (!lastReport) { return; }
                try {
                    await navigator.clipboard.writeText(lastReport);
                    alert(rtbcbAdmin.strings.copied);
                } catch (err) {
                    alert(rtbcbAdmin.strings.error + ' ' + err.message);
                }
            });

            runBtn.on('click', async function(){
                results.empty();
                runBtn.prop('disabled', true).text(rtbcbAdmin.strings.testing);
                const summary = $('<div />');

                // API test
                try {
                    const apiResp = await $.post(rtbcbAdmin.ajax_url, {
                        action: 'rtbcb_test_api',
                        nonce: rtbcbAdmin.test_api_nonce
                    });
                    summary.append($('<p />').text('API: ' + (apiResp.success ? 'OK' : 'FAIL')));
                } catch (err) {
                    summary.append($('<p />').text('API: FAIL'));
                }

                // Complete report test
                try {
                    const formData = new FormData();
                    formData.append('action', 'rtbcb_test_complete_report');
                    formData.append('nonce', rtbcbAdmin.complete_report_nonce);
                    const response = await fetch(rtbcbAdmin.ajax_url, { method: 'POST', body: formData });
                    const data = await response.json();
                    if (data.success) {
                        const secList = $('<ul />');
                        Object.entries(data.data.sections).forEach(([key, val]) => {
                            const status = val.success ? 'OK' : 'FAIL';
                            secList.append($('<li />').text(key + ': ' + status));
                        });
                        summary.append(secList);
                        summary.append($('<p />').text('Words: ' + data.data.word_count + ' | Time: ' + data.data.elapsed + 's | Generated: ' + data.data.generated));
                        const reportDiv = $('<div class="rtbcb-test-report" />').html(data.data.report_html);
                        results.append(summary).append(reportDiv);
                        lastReport = reportDiv.text();
                    } else {
                        summary.append($('<p />').text('Report: FAIL'));
                        results.append(summary);
                    }
                } catch (err) {
                    summary.append($('<p />').text('Report: FAIL'));
                    results.append(summary);
                }

                runBtn.prop('disabled', false).text('Test All Sections');
            });
        },

        async testApiConnection(e) {
            e.preventDefault();
            const button = $(this);
            const label = button.find('h4');
            const original = label.text();
            label.text(rtbcbAdmin.strings.processing);
            button.prop('disabled', true);

            try {
                const formData = new FormData();
                formData.append('action', 'rtbcb_test_connection');
                formData.append('nonce', rtbcbAdmin.nonce);

                const response = await fetch(rtbcbAdmin.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                if (!response.ok) {
                    throw new Error(`Server responded ${response.status}`);
                }
                const data = await response.json();
                alert(data.success ? 'API connection successful!' : (rtbcbAdmin.strings.error + (data.data?.message || '')));
            } catch (err) {
                alert(`${rtbcbAdmin.strings.error} ${err.message}`);
            }
            label.text(original);
            button.prop('disabled', false);
        },

        async rebuildIndex(e) {
            e.preventDefault();
            const button = $(this);
            const original = button.text();
            button.text(rtbcbAdmin.strings.processing).prop('disabled', true);
            try {
                const formData = new FormData();
                formData.append('action', 'rtbcb_rebuild_index');
                formData.append('nonce', rtbcbAdmin.nonce);
                const response = await fetch(rtbcbAdmin.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                if (!response.ok) {
                    throw new Error(`Server responded ${response.status}`);
                }
                const data = await response.json();
                if (data.success) {
                    alert('RAG index rebuilt successfully');
                    location.reload();
                } else {
                    alert(data.data?.message || rtbcbAdmin.strings.error);
                }
            } catch (err) {
                alert(`${rtbcbAdmin.strings.error} ${err.message}`);
            }
            button.text(original).prop('disabled', false);
        },

        async exportLeads(e) {
            e.preventDefault();
            const button = $(this);
            const label = button.find('h4').length ? button.find('h4') : button;
            const original = label.text();
            label.text(rtbcbAdmin.strings.processing);
            button.prop('disabled', true);

            try {
                const params = new URLSearchParams(window.location.search);
                const formData = new FormData();
                formData.append('action', 'rtbcb_export_leads');
                formData.append('nonce', rtbcbAdmin.nonce);
                formData.append('search', params.get('search') || '');
                formData.append('category', params.get('category') || '');
                formData.append('date_from', params.get('date_from') || '');
                formData.append('date_to', params.get('date_to') || '');

                const response = await fetch(rtbcbAdmin.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                if (!response.ok) {
                    throw new Error(`Server responded ${response.status}`);
                }
                const data = await response.json();
                if (data.success) {
                    const blob = new Blob([data.data.content], {type: 'text/csv'});
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = data.data.filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                } else {
                    alert(data.data?.message || rtbcbAdmin.strings.error);
                }
            } catch(err) {
                alert(`${rtbcbAdmin.strings.error} ${err.message}`);
            }
            label.text(original);
            button.prop('disabled', false);
        },

        async runDiagnostics(e) {
            e.preventDefault();
            const button = $(this);
            button.prop('disabled', true);

            try {
                const formData = new FormData();
                formData.append('action', 'rtbcb_run_tests');
                formData.append('nonce', $(this).data('nonce') || rtbcbAdmin.nonce);

                const response = await fetch(rtbcbAdmin.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                if (!response.ok) {
                    throw new Error(`Server responded ${response.status}`);
                }
                const data = await response.json();
                if (data.success) {
                    let message = '';
                    for (const [key, result] of Object.entries(data.data)) {
                        message += `${key}: ${result.passed ? 'PASS' : 'FAIL'} - ${result.message}\n`;
                    }
                    alert(message);
                    console.log('Diagnostics results:', data.data);
                } else {
                    alert(data.data?.message || rtbcbAdmin.strings.error);
                }
            } catch (err) {
                console.error('Diagnostics error:', err);
                alert(`${rtbcbAdmin.strings.error} ${err.message}`);
            }

            button.prop('disabled', false);
        },

        async syncToLocal(e) {
            e.preventDefault();
            const button = $(this);
            const original = button.text();
            button.text(rtbcbAdmin.strings.processing).prop('disabled', true);

            try {
                const nonce = $('#rtbcb-sync-local-form').find('input[name="rtbcb_sync_local_nonce"]').val();
                const formData = new FormData();
                formData.append('action', 'rtbcb_sync_to_local');
                formData.append('nonce', nonce);
                const response = await fetch(rtbcbAdmin.ajax_url, { method: 'POST', body: formData });
                if (!response.ok) {
                    throw new Error(`Server responded ${response.status}`);
                }
                const data = await response.json();
                alert(data.data?.message || rtbcbAdmin.strings.error);
            } catch (err) {
                alert(`${rtbcbAdmin.strings.error} ${err.message}`);
            }

            button.text(original).prop('disabled', false);
        },

        initLeadsManager() {
            if (document.querySelector('#rtbcb-bulk-form')) {
                new RTBCBLeadsManager();
            }
        }
    };

    class RTBCBLeadsManager {
        constructor() {
            this.bindEvents();
            this.updateBulkActionButton();
        }

        bindEvents() {
            const selectAll = document.getElementById('rtbcb-select-all');
            if (selectAll) {
                selectAll.addEventListener('change', this.toggleSelectAll.bind(this));
            }
            document.querySelectorAll('.rtbcb-lead-checkbox').forEach(cb => {
                cb.addEventListener('change', this.updateSelectAll.bind(this));
                cb.addEventListener('change', this.updateBulkActionButton.bind(this));
            });
            const bulkForm = document.getElementById('rtbcb-bulk-form');
            if (bulkForm) {
                bulkForm.addEventListener('submit', this.handleBulkAction.bind(this));
            }
            document.querySelectorAll('.rtbcb-view-lead').forEach(btn => {
                btn.addEventListener('click', this.viewLeadDetails.bind(this));
            });
            document.querySelectorAll('.rtbcb-delete-lead').forEach(btn => {
                btn.addEventListener('click', this.deleteLead.bind(this));
            });
            document.querySelector('.rtbcb-modal-close')?.addEventListener('click', this.closeModal.bind(this));
            document.getElementById('rtbcb-lead-modal')?.addEventListener('click', (e)=>{
                if (e.target.id === 'rtbcb-lead-modal') { this.closeModal(); }
            });
        }

        toggleSelectAll(e) {
            const checked = e.target.checked;
            document.querySelectorAll('.rtbcb-lead-checkbox').forEach(cb => { cb.checked = checked; });
            this.updateBulkActionButton();
        }

        updateSelectAll() {
            const boxes = document.querySelectorAll('.rtbcb-lead-checkbox');
            const checked = document.querySelectorAll('.rtbcb-lead-checkbox:checked');
            const selectAll = document.getElementById('rtbcb-select-all');
            if (selectAll) {
                selectAll.checked = boxes.length === checked.length && boxes.length > 0;
                selectAll.indeterminate = checked.length > 0 && checked.length < boxes.length;
            }
        }

        updateBulkActionButton() {
            const count = document.querySelectorAll('.rtbcb-lead-checkbox:checked').length;
            const button = document.querySelector('#rtbcb-bulk-form button[type="submit"]');
            if (button) {
                button.disabled = count === 0;
            }
        }

        async handleBulkAction(e) {
            e.preventDefault();
            const action = document.getElementById('rtbcb-bulk-action').value;
            const ids = Array.from(document.querySelectorAll('.rtbcb-lead-checkbox:checked')).map(cb => cb.value);
            if (!action || ids.length === 0) { return; }
            if (action === 'delete' && !confirm(rtbcbAdmin.strings.confirm_bulk_delete)) { return; }
            try {
                const formData = new FormData();
                formData.append('action', 'rtbcb_bulk_action_leads');
                formData.append('nonce', rtbcbAdmin.nonce);
                formData.append('action', action);
                formData.append('lead_ids', JSON.stringify(ids));
                const response = await fetch(rtbcbAdmin.ajax_url, { method: 'POST', body: formData });
                if (!response.ok) {
                    throw new Error(`Server responded ${response.status}`);
                }
                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.data?.message || rtbcbAdmin.strings.error);
                }
            } catch(err){
                alert(`${rtbcbAdmin.strings.error} ${err.message}`);
            }
        }

        async viewLeadDetails(e) {
            e.preventDefault();
            const row = e.currentTarget.closest('tr');
            const email = row.querySelector('.column-email strong').textContent;
            const companySize = row.querySelector('.column-company-size').textContent.trim();
            const category = row.querySelector('.column-category').textContent.trim();
            const roi = row.querySelector('.column-roi').textContent.trim();
            const date = row.querySelector('.column-date').textContent.trim();
            const detailsHtml = `
                <div class="rtbcb-lead-detail-grid">
                    <div class="rtbcb-detail-item"><label>Email:</label><span>${email}</span></div>
                    <div class="rtbcb-detail-item"><label>Company Size:</label><span>${companySize}</span></div>
                    <div class="rtbcb-detail-item"><label>Recommended Category:</label><span>${category}</span></div>
                    <div class="rtbcb-detail-item"><label>Base ROI:</label><span>${roi}</span></div>
                    <div class="rtbcb-detail-item"><label>Submitted:</label><span>${date}</span></div>
                </div>`;
            document.getElementById('rtbcb-lead-details').innerHTML = detailsHtml;
            document.getElementById('rtbcb-lead-modal').style.display = 'block';
        }

        async deleteLead(e) {
            e.preventDefault();
            if (!confirm(rtbcbAdmin.strings.confirm_delete)) { return; }
            const id = e.currentTarget.dataset.leadId;
            try {
                const formData = new FormData();
                formData.append('action', 'rtbcb_delete_lead');
                formData.append('nonce', rtbcbAdmin.nonce);
                formData.append('lead_id', id);
                const response = await fetch(rtbcbAdmin.ajax_url, { method: 'POST', body: formData });
                if (!response.ok) {
                    throw new Error(`Server responded ${response.status}`);
                }
                const data = await response.json();
                if (data.success) {
                    e.currentTarget.closest('tr').remove();
                    this.updateBulkActionButton();
                } else {
                    alert(data.data?.message || rtbcbAdmin.strings.error);
                }
            } catch(err){
                alert(`${rtbcbAdmin.strings.error} ${err.message}`);
            }
        }

        bindReportPreview() {
            const form = document.getElementById('rtbcb-report-preview-form');
            if (!form) { return; }
            form.addEventListener('submit', this.generateReportPreview.bind(this));
            document.getElementById('rtbcb-download-pdf')?.addEventListener('click', this.downloadReportPDF.bind(this));
            const select = document.getElementById('rtbcb-sample-select');
            if (select) {
                const injectSample = () => {
                    const key = select.value;
                    const target = document.getElementById('rtbcb-sample-context');
                    if (key && target && rtbcbAdmin.sampleForms && rtbcbAdmin.sampleForms[key]) {
                        target.value = JSON.stringify(rtbcbAdmin.sampleForms[key], null, 2);
                    }
                };
                select.addEventListener('change', injectSample);
                document.getElementById('rtbcb-load-sample')?.addEventListener('click', injectSample);
            }
        },

        async generateReportPreview(e) {
            e.preventDefault();
            const form = e.currentTarget;
            const button = document.getElementById('rtbcb-generate-report');
            const original = button.textContent;
            button.textContent = rtbcbAdmin.strings.processing;
            button.disabled = true;
            try {
                const formData = new FormData(form);
                const select = document.getElementById('rtbcb-sample-select');
                const sampleKey = select && select.value ? select.value.trim() : '';
                if (sampleKey === '') {
                    formData.set('action', 'rtbcb_generate_report_preview');
                } else {
                    formData.set('action', 'rtbcb_generate_sample_report');
                    formData.append('scenario_key', sampleKey);
                }
                const response = await fetch(rtbcbAdmin.ajax_url, { method: 'POST', body: formData });
                if (!response.ok) {
                    console.error('Report preview request failed with status', response.status);
                    const text = await response.text();
                    console.error('Response body:', text);
                    alert(`${rtbcbAdmin.strings.error} ${response.status}: ${text}`);
                    return;
                }
                const data = await response.json();
                if (data.success) {
                    const iframe = document.getElementById('rtbcb-report-iframe');
                    if (iframe) { iframe.srcdoc = data.data.html || data.data.report_html; }
                    document.getElementById('rtbcb-download-pdf').style.display = 'inline-block';
                } else {
                    alert(data.data?.message || rtbcbAdmin.strings.error);
                }
            } catch(err) {
                alert(`${rtbcbAdmin.strings.error} ${err.message}`);
            }
            button.textContent = original;
            button.disabled = false;
        },

        bindSampleReport() {
            const button = document.getElementById('rtbcb-generate-sample-report');
            if (!button) { return; }
            button.addEventListener('click', this.generateSampleReport.bind(this));
        },

        async generateSampleReport(e) {
            e.preventDefault();
            const button = e.currentTarget;
            const original = button.textContent;
            button.textContent = rtbcbAdmin.strings.processing;
            button.disabled = true;
            try {
                const formData = new FormData();
                const nonceField = document.getElementById('nonce');
                const nonce = nonceField ? nonceField.value : (rtbcbAdmin?.report_preview_nonce || '');
                formData.append('action', 'rtbcb_generate_sample_report');
                formData.append('nonce', nonce);
                const response = await fetch(rtbcbAdmin.ajax_url, { method: 'POST', body: formData });
                if (!response.ok) {
                    console.error('Sample report request failed with status', response.status);
                    const text = await response.text();
                    console.error('Response body:', text);
                    alert(`${rtbcbAdmin.strings.error} ${response.status}: ${text}`);
                    return;
                }
                const data = await response.json();
                if (data.success) {
                    const iframe = document.getElementById('rtbcb-sample-report-frame');
                    if (iframe) { iframe.srcdoc = data.data.report_html; }
                } else {
                    alert(data.data?.message || rtbcbAdmin.strings.error);
                }
            } catch(err) {
                alert(`${rtbcbAdmin.strings.error} ${err.message}`);
            }
            button.textContent = original;
            button.disabled = false;
        },

        downloadReportPDF(e) {
            e.preventDefault();
            const iframe = document.getElementById('rtbcb-report-iframe');
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            }
        },

        closeModal() {
            const modal = document.getElementById('rtbcb-lead-modal');
            if (modal) { modal.style.display = 'none'; }
        }
    }

    $(function(){ RTBCBAdmin.init(); });

})(jQuery);
