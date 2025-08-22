(function($){
    'use strict';

    const RTBCBAdmin = {
        init() {
            this.bindDashboardActions();
            this.bindExportButtons();
            this.initLeadsManager();
            this.bindDiagnosticsButton();
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
                const data = await response.json();
                alert(data.success ? 'API connection successful!' : (rtbcbAdmin.strings.error + (data.data?.message || '')));
            } catch (err) {
                alert(rtbcbAdmin.strings.error);
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
                const data = await response.json();
                if (data.success) {
                    alert('RAG index rebuilt successfully');
                    location.reload();
                } else {
                    alert(data.data?.message || rtbcbAdmin.strings.error);
                }
            } catch (err) {
                alert(rtbcbAdmin.strings.error);
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
                alert(rtbcbAdmin.strings.error);
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
                alert(rtbcbAdmin.strings.error);
            }

            button.prop('disabled', false);
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
                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.data?.message || rtbcbAdmin.strings.error);
                }
            } catch(err){
                alert(rtbcbAdmin.strings.error);
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
                const data = await response.json();
                if (data.success) {
                    e.currentTarget.closest('tr').remove();
                    this.updateBulkActionButton();
                } else {
                    alert(data.data?.message || rtbcbAdmin.strings.error);
                }
            } catch(err){
                alert(rtbcbAdmin.strings.error);
            }
        }

        closeModal() {
            const modal = document.getElementById('rtbcb-lead-modal');
            if (modal) { modal.style.display = 'none'; }
        }
    }

    $(function(){ RTBCBAdmin.init(); });

})(jQuery);
