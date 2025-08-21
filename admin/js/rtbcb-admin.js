/* global rtbcbAdmin, Chart */

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
            selectAll.addEventListener('change', this.toggleSelectAll.bind(this));
        }

        document.querySelectorAll('.rtbcb-lead-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', this.updateSelectAll.bind(this));
            checkbox.addEventListener('change', this.updateBulkActionButton.bind(this));
        });

        const bulkForm = document.getElementById('rtbcb-bulk-form');
        if (bulkForm) {
            bulkForm.addEventListener('submit', this.handleBulkAction.bind(this));
        }

        const exportBtn = document.getElementById('rtbcb-export-leads');
        if (exportBtn) {
            exportBtn.addEventListener('click', this.exportLeads.bind(this));
        }

        document.querySelectorAll('.rtbcb-view-lead').forEach(btn => {
            btn.addEventListener('click', this.viewLeadDetails.bind(this));
        });

        document.querySelectorAll('.rtbcb-delete-lead').forEach(btn => {
            btn.addEventListener('click', this.deleteLead.bind(this));
        });

        const modalClose = document.querySelector('.rtbcb-modal-close');
        if (modalClose) {
            modalClose.addEventListener('click', this.closeModal.bind(this));
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
        const isChecked = e.target.checked;
        document.querySelectorAll('.rtbcb-lead-checkbox').forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        this.updateBulkActionButton();
    }

    updateSelectAll() {
        const checkboxes = document.querySelectorAll('.rtbcb-lead-checkbox');
        const checkedBoxes = document.querySelectorAll('.rtbcb-lead-checkbox:checked');
        const selectAll = document.getElementById('rtbcb-select-all');

        if (selectAll) {
            selectAll.checked = checkboxes.length === checkedBoxes.length && checkboxes.length > 0;
            selectAll.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < checkboxes.length;
        }
    }

    updateBulkActionButton() {
        const checkedBoxes = document.querySelectorAll('.rtbcb-lead-checkbox:checked');
        const bulkButton = document.querySelector('#rtbcb-bulk-form button[type="submit"]');

        if (bulkButton) {
            bulkButton.disabled = checkedBoxes.length === 0;
        }
    }

    async handleBulkAction(e) {
        e.preventDefault();

        const actionSelect = document.getElementById('rtbcb-bulk-action');
        const action = actionSelect ? actionSelect.value : '';
        const checkedBoxes = document.querySelectorAll('.rtbcb-lead-checkbox:checked');

        if (!action || checkedBoxes.length === 0) {
            return;
        }

        const leadIds = Array.from(checkedBoxes).map(cb => cb.value);

        if (action === 'delete') {
            if (!confirm(rtbcbAdmin.strings.confirm_bulk_delete)) {
                return;
            }
        }

        try {
            const formData = new FormData();
            formData.append('action', 'rtbcb_bulk_action_leads');
            formData.append('nonce', rtbcbAdmin.nonce);
            formData.append('bulk_action', action);
            leadIds.forEach(id => formData.append('lead_ids[]', id));

            const response = await fetch(rtbcbAdmin.ajax_url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                location.reload();
            } else {
                alert(data.data.message || rtbcbAdmin.strings.error);
            }
        } catch (error) {
            /* eslint-disable no-console */
            console.error('Bulk action error:', error);
            /* eslint-enable no-console */
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

            const response = await fetch(rtbcbAdmin.ajax_url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

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
        } catch (error) {
            /* eslint-disable no-console */
            console.error('Export error:', error);
            /* eslint-enable no-console */
            alert(rtbcbAdmin.strings.error);
        }
    }

    viewLeadDetails(e) {
        e.preventDefault();
        const leadId = e.currentTarget.dataset.leadId;
        const row = e.currentTarget.closest('tr');
        const email = row.querySelector('.column-email strong').textContent;
        const companySize = row.querySelector('.column-company-size').textContent.trim();
        const category = row.querySelector('.column-category').textContent.trim();
        const roi = row.querySelector('.column-roi').textContent.trim();
        const date = row.querySelector('.column-date').textContent.trim();

        const detailsHtml = `
            <div class="rtbcb-lead-detail-grid">
                <div class="rtbcb-detail-item">
                    <label>Email:</label>
                    <span>${email}</span>
                </div>
                <div class="rtbcb-detail-item">
                    <label>Company Size:</label>
                    <span>${companySize}</span>
                </div>
                <div class="rtbcb-detail-item">
                    <label>Recommended Category:</label>
                    <span>${category}</span>
                </div>
                <div class="rtbcb-detail-item">
                    <label>Base ROI:</label>
                    <span>${roi}</span>
                </div>
                <div class="rtbcb-detail-item">
                    <label>Submitted:</label>
                    <span>${date}</span>
                </div>
            </div>
        `;

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

            const response = await fetch(rtbcbAdmin.ajax_url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                const row = e.currentTarget.closest('tr');
                row.remove();
                this.updateBulkActionButton();
            } else {
                alert(data.data.message || rtbcbAdmin.strings.error);
            }
        } catch (error) {
            /* eslint-disable no-console */
            console.error('Delete error:', error);
            /* eslint-enable no-console */
            alert(rtbcbAdmin.strings.error);
        }
    }

    closeModal() {
        document.getElementById('rtbcb-lead-modal').style.display = 'none';
    }
}

function initDashboard() {
    const testBtn = document.getElementById('rtbcb-test-api');
    if (testBtn) {
        testBtn.addEventListener('click', async function () {
            const button = this;
            const originalText = button.querySelector('h4').textContent;

            button.querySelector('h4').textContent = rtbcbAdmin.strings.testing;
            button.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'rtbcb_test_connection');
                formData.append('nonce', rtbcbAdmin.nonce);

                const response = await fetch(rtbcbAdmin.ajax_url, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert(rtbcbAdmin.strings.api_success);
                } else {
                    alert(rtbcbAdmin.strings.api_failed + (data.data.message || rtbcbAdmin.strings.unknown_error));
                }
            } catch (error) {
                alert(rtbcbAdmin.strings.network_error);
            } finally {
                button.querySelector('h4').textContent = originalText;
                button.disabled = false;
            }
        });
    }

    const rebuildBtn = document.getElementById('rtbcb-rebuild-index');
    if (rebuildBtn) {
        rebuildBtn.addEventListener('click', async function () {
            const button = this;
            const originalText = button.textContent;

            button.textContent = rtbcbAdmin.strings.rebuilding;
            button.disabled = true;

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
                    alert(rtbcbAdmin.strings.rag_success);
                    window.location.reload();
                } else {
                    alert(rtbcbAdmin.strings.rag_failed + (data.data.message || rtbcbAdmin.strings.unknown_error));
                }
            } catch (error) {
                alert(rtbcbAdmin.strings.network_error);
            } finally {
                button.textContent = originalText;
                button.disabled = false;
            }
        });
    }

    const exportBtn = document.getElementById('rtbcb-export-data');
    if (exportBtn) {
        exportBtn.addEventListener('click', async function () {
            const button = this;
            const titleEl = button.querySelector('h4');
            const originalText = titleEl.textContent;

            titleEl.textContent = rtbcbAdmin.strings.exporting;
            button.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'rtbcb_export_leads');
                formData.append('nonce', rtbcbAdmin.nonce);

                const response = await fetch(rtbcbAdmin.ajax_url, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

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
                    alert(rtbcbAdmin.strings.export_failed + (data.data.message || rtbcbAdmin.strings.unknown_error));
                }
            } catch (error) {
                alert(rtbcbAdmin.strings.network_error);
            } finally {
                titleEl.textContent = originalText;
                button.disabled = false;
            }
        });
    }
}

function initAnalyticsCharts() {
    if (typeof window.rtbcbAnalyticsData === 'undefined') {
        return;
    }

    const data = window.rtbcbAnalyticsData;

    if (data.category_stats && data.category_stats.length > 0) {
        const categoryLabels = data.category_stats.map(item => {
            return data.categories[item.recommended_category]?.name || item.recommended_category;
        });
        const categoryValues = data.category_stats.map(item => item.count);
        const categoryColors = ['#7216f4', '#8f47f6', '#c77dff', '#e0aaff', '#f3c4fb'];

        /* eslint-disable no-new */
        new Chart(document.getElementById('rtbcb-category-chart'), {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryValues,
                    backgroundColor: categoryColors.slice(0, categoryValues.length),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: context => {
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        /* eslint-enable no-new */
    }

    if (data.size_stats && data.size_stats.length > 0) {
        const sizeLabels = data.size_stats.map(item => item.company_size);
        const sizeValues = data.size_stats.map(item => item.count);
        const sizeColors = ['#dbeafe', '#dcfce7', '#fef3c7', '#fde2e8'];

        /* eslint-disable no-new */
        new Chart(document.getElementById('rtbcb-size-chart'), {
            type: 'bar',
            data: {
                labels: sizeLabels,
                datasets: [{
                    data: sizeValues,
                    backgroundColor: sizeColors.slice(0, sizeValues.length),
                    borderColor: ['#1e40af', '#166534', '#92400e', '#be185d'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
        /* eslint-enable no-new */
    }

    if (data.monthly_trends && data.monthly_trends.length > 0) {
        const trendLabels = data.monthly_trends.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        });
        const leadCounts = data.monthly_trends.map(item => parseInt(item.leads, 10));
        const avgROIs = data.monthly_trends.map(item => Math.round(parseFloat(item.avg_roi || 0) / 1000));

        /* eslint-disable no-new */
        new Chart(document.getElementById('rtbcb-trends-chart'), {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [
                    {
                        label: 'Leads',
                        data: leadCounts,
                        backgroundColor: 'rgba(114, 22, 244, 0.1)',
                        borderColor: '#7216f4',
                        borderWidth: 2,
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Avg ROI (K)',
                        data: avgROIs,
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderColor: '#10b981',
                        borderWidth: 2,
                        fill: true,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        title: { display: true, text: 'Number of Leads' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        title: { display: true, text: 'Average ROI (Thousands)' },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
        /* eslint-enable no-new */
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('rtbcb-bulk-form')) {
        new RTBCBLeadsManager();
    }
    initDashboard();
    initAnalyticsCharts();
});
