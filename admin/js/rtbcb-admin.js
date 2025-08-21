(function() {
    function initDashboard() {
        const testBtn = document.getElementById('rtbcb-test-api');
        if (testBtn) {
            testBtn.addEventListener('click', async () => {
                const original = testBtn.querySelector('h4') ? testBtn.querySelector('h4').textContent : testBtn.textContent;
                if (testBtn.querySelector('h4')) {
                    testBtn.querySelector('h4').textContent = rtbcbAdmin.strings.processing;
                }
                testBtn.disabled = true;
                try {
                    const formData = new FormData();
                    formData.append('action', 'rtbcb_test_connection');
                    formData.append('nonce', rtbcbAdmin.nonce);
                    const res = await fetch(rtbcbAdmin.ajax_url, { method: 'POST', body: formData });
                    const data = await res.json();
                    if (data.success) {
                        alert('API connection successful!');
                    } else {
                        alert('API connection failed: ' + (data.data.message || 'Unknown error'));
                    }
                } catch (e) {
                    alert('Network error occurred');
                } finally {
                    if (testBtn.querySelector('h4')) {
                        testBtn.querySelector('h4').textContent = original;
                    }
                    testBtn.disabled = false;
                }
            });
        }

        const rebuildBtn = document.getElementById('rtbcb-rebuild-index');
        if (rebuildBtn) {
            rebuildBtn.addEventListener('click', async () => {
                const original = rebuildBtn.textContent;
                rebuildBtn.textContent = rtbcbAdmin.strings.processing;
                rebuildBtn.disabled = true;
                try {
                    const formData = new FormData();
                    formData.append('action', 'rtbcb_rebuild_index');
                    formData.append('nonce', rtbcbAdmin.nonce);
                    const res = await fetch(rtbcbAdmin.ajax_url, { method: 'POST', body: formData });
                    const data = await res.json();
                    if (data.success) {
                        alert('RAG index rebuilt successfully!');
                        location.reload();
                    } else {
                        alert('Failed to rebuild index: ' + (data.data.message || 'Unknown error'));
                    }
                } catch (e) {
                    alert('Network error occurred');
                } finally {
                    rebuildBtn.textContent = original;
                    rebuildBtn.disabled = false;
                }
            });
        }

        const exportBtn = document.getElementById('rtbcb-export-data');
        if (exportBtn) {
            exportBtn.addEventListener('click', async () => {
                const original = exportBtn.querySelector('h4') ? exportBtn.querySelector('h4').textContent : exportBtn.textContent;
                if (exportBtn.querySelector('h4')) {
                    exportBtn.querySelector('h4').textContent = rtbcbAdmin.strings.processing;
                }
                exportBtn.disabled = true;
                try {
                    const formData = new FormData();
                    formData.append('action', 'rtbcb_export_leads');
                    formData.append('nonce', rtbcbAdmin.nonce);
                    const res = await fetch(rtbcbAdmin.ajax_url, { method: 'POST', body: formData });
                    const data = await res.json();
                    if (data.success) {
                        const blob = new Blob([data.data.content], { type: 'text/csv' });
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = data.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(url);
                    } else {
                        alert('Export failed: ' + (data.data.message || 'Unknown error'));
                    }
                } catch (e) {
                    alert('Network error occurred');
                } finally {
                    if (exportBtn.querySelector('h4')) {
                        exportBtn.querySelector('h4').textContent = original;
                    }
                    exportBtn.disabled = false;
                }
            });
        }
    }

    class RTBCBLeadsManager {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.updateBulkActionButton();
        }

        bindEvents() {
            const selectAll = document.getElementById('rtbcb-select-all');
            if (selectAll) {
                selectAll.addEventListener('change', e => this.toggleSelectAll(e));
            }

            document.querySelectorAll('.rtbcb-lead-checkbox').forEach(cb => {
                cb.addEventListener('change', () => {
                    this.updateSelectAll();
                    this.updateBulkActionButton();
                });
            });

            const bulkForm = document.getElementById('rtbcb-bulk-form');
            if (bulkForm) {
                bulkForm.addEventListener('submit', e => this.handleBulkAction(e));
            }

            const exportBtn = document.getElementById('rtbcb-export-leads');
            if (exportBtn) {
                exportBtn.addEventListener('click', () => this.exportLeads());
            }

            document.querySelectorAll('.rtbcb-view-lead').forEach(btn => {
                btn.addEventListener('click', e => this.viewLeadDetails(e));
            });

            document.querySelectorAll('.rtbcb-delete-lead').forEach(btn => {
                btn.addEventListener('click', e => this.deleteLead(e));
            });

            const modalClose = document.querySelector('.rtbcb-modal-close');
            if (modalClose) {
                modalClose.addEventListener('click', () => this.closeModal());
            }

            const modal = document.getElementById('rtbcb-lead-modal');
            if (modal) {
                modal.addEventListener('click', e => {
                    if (e.target === modal) {
                        this.closeModal();
                    }
                });
            }
        }

        toggleSelectAll(e) {
            const checked = e.target.checked;
            document.querySelectorAll('.rtbcb-lead-checkbox').forEach(cb => {
                cb.checked = checked;
            });
            this.updateBulkActionButton();
        }

        updateSelectAll() {
            const checkboxes = document.querySelectorAll('.rtbcb-lead-checkbox');
            const checked = document.querySelectorAll('.rtbcb-lead-checkbox:checked');
            const selectAll = document.getElementById('rtbcb-select-all');
            if (selectAll) {
                selectAll.checked = checkboxes.length === checked.length && checkboxes.length > 0;
                selectAll.indeterminate = checked.length > 0 && checked.length < checkboxes.length;
            }
        }

        updateBulkActionButton() {
            const checked = document.querySelectorAll('.rtbcb-lead-checkbox:checked');
            const bulkButton = document.querySelector('#rtbcb-bulk-form button[type="submit"]');
            if (bulkButton) {
                bulkButton.disabled = checked.length === 0;
            }
        }

        async handleBulkAction(e) {
            e.preventDefault();
            const action = document.getElementById('rtbcb-bulk-action').value;
            const checked = Array.from(document.querySelectorAll('.rtbcb-lead-checkbox:checked'));
            if (!action || checked.length === 0) {
                return;
            }
            if (action === 'delete' && !confirm(rtbcbAdmin.strings.confirm_bulk_delete)) {
                return;
            }
            try {
                const formData = new FormData();
                formData.append('action', 'rtbcb_bulk_action_leads');
                formData.append('nonce', rtbcbAdmin.nonce);
                formData.append('bulk_action', action);
                checked.forEach(cb => formData.append('lead_ids[]', cb.value));
                const res = await fetch(rtbcbAdmin.ajax_url, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.data.message || rtbcbAdmin.strings.error);
                }
            } catch (err) {
                alert(rtbcbAdmin.strings.error);
            }
        }

        async exportLeads() {
            try {
                const params = new URLSearchParams(window.location.search);
                const formData = new FormData();
                formData.append('action', 'rtbcb_export_leads');
                formData.append('nonce', rtbcbAdmin.nonce);
                formData.append('search', params.get('search') || '');
                formData.append('category', params.get('category') || '');
                formData.append('date_from', params.get('date_from') || '');
                formData.append('date_to', params.get('date_to') || '');
                const res = await fetch(rtbcbAdmin.ajax_url, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    const blob = new Blob([data.data.content], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = data.data.filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                } else {
                    alert(data.data.message || rtbcbAdmin.strings.error);
                }
            } catch (err) {
                alert(rtbcbAdmin.strings.error);
            }
        }

        viewLeadDetails(e) {
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
            if (!confirm(rtbcbAdmin.strings.confirm_delete)) {
                return;
            }
            const leadId = e.currentTarget.dataset.leadId;
            try {
                const formData = new FormData();
                formData.append('action', 'rtbcb_delete_lead');
                formData.append('nonce', rtbcbAdmin.nonce);
                formData.append('lead_id', leadId);
                const res = await fetch(rtbcbAdmin.ajax_url, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    const row = e.currentTarget.closest('tr');
                    row.remove();
                    this.updateBulkActionButton();
                } else {
                    alert(data.data.message || rtbcbAdmin.strings.error);
                }
            } catch (err) {
                alert(rtbcbAdmin.strings.error);
            }
        }

        closeModal() {
            const modal = document.getElementById('rtbcb-lead-modal');
            if (modal) {
                modal.style.display = 'none';
            }
        }
    }

    function initAnalyticsCharts() {
        const container = document.querySelector('.rtbcb-admin-page[data-category-stats]');
        if (!container || typeof Chart === 'undefined') {
            return;
        }
        const categories = JSON.parse(container.dataset.categories || '{}');
        const categoryData = JSON.parse(container.dataset.categoryStats || '[]');
        const sizeData = JSON.parse(container.dataset.sizeStats || '[]');
        const trendsData = JSON.parse(container.dataset.monthlyTrends || '[]');
        const totalLeads = parseInt(container.dataset.totalLeads || '0', 10);

        if (categoryData.length > 0) {
            const labels = categoryData.map(item => categories[item.recommended_category]?.name || item.recommended_category);
            const values = categoryData.map(item => item.count);
            new Chart(document.getElementById('rtbcb-category-chart'), {
                type: 'doughnut',
                data: { labels, datasets: [{ data: values, backgroundColor: ['#7216f4','#8f47f6','#c77dff','#e0aaff','#f3c4fb'], borderWidth:0 }] },
                options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } } }
            });
        }

        if (sizeData.length > 0) {
            const labels = sizeData.map(item => item.company_size);
            const values = sizeData.map(item => item.count);
            new Chart(document.getElementById('rtbcb-size-chart'), {
                type: 'bar',
                data: { labels, datasets:[{ data: values, backgroundColor:['#dbeafe','#dcfce7','#fef3c7','#fde2e8'], borderColor:['#1e40af','#166534','#92400e','#be185d'], borderWidth:1 }] },
                options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true, ticks:{ stepSize:1 } } } }
            });
        }

        if (trendsData.length > 0) {
            const labels = trendsData.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            });
            const leadCounts = trendsData.map(item => parseInt(item.leads, 10));
            const avgROIs = trendsData.map(item => Math.round(parseFloat(item.avg_roi || 0) / 1000));
            new Chart(document.getElementById('rtbcb-trends-chart'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        { label: 'Leads', data: leadCounts, backgroundColor: 'rgba(114,22,244,0.1)', borderColor: '#7216f4', borderWidth:2, fill:true, yAxisID:'y' },
                        { label: 'Avg ROI (K)', data: avgROIs, backgroundColor: 'rgba(16,185,129,0.1)', borderColor: '#10b981', borderWidth:2, fill:true, yAxisID:'y1' }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        y: { type: 'linear', display:true, position:'left', beginAtZero:true, title:{ display:true, text:'Number of Leads' } },
                        y1: { type: 'linear', display:true, position:'right', beginAtZero:true, title:{ display:true, text:'Average ROI (Thousands)' }, grid:{ drawOnChartArea:false } }
                    }
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        initDashboard();
        if (document.getElementById('rtbcb-lead-modal')) {
            new RTBCBLeadsManager();
        }
        initAnalyticsCharts();
    });
})();
