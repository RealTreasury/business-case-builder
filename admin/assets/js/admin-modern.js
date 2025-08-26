/**
 * Modern Admin JavaScript for Real Treasury Business Case Builder
 * Handles AJAX interactions, modal management, and dynamic UI updates
 */

(function($) {
    'use strict';

    // Admin object for namespace
    window.RTBCBAdmin = window.RTBCBAdmin || {};

    /**
     * Main admin functionality
     */
    const Admin = {
        /**
         * Initialize admin functionality
         */
        init() {
            this.bindEvents();
            this.initModals();
            this.initCharts();
            this.initBulkActions();
            this.initSearch();
            this.setupAjaxErrorHandling();
        },

        /**
         * Bind event handlers
         */
        bindEvents() {
            // Form submissions
            $(document).on('submit', '.rtbcb-ajax-form', this.handleAjaxForm.bind(this));
            
            // Button clicks
            $(document).on('click', '.rtbcb-btn[data-action]', this.handleButtonAction.bind(this));
            
            // Modal triggers
            $(document).on('click', '[data-modal]', this.openModal.bind(this));
            $(document).on('click', '.rtbcb-modal-close, .rtbcb-modal-overlay', this.closeModal.bind(this));
            
            // Table actions
            $(document).on('click', '.rtbcb-view-lead', this.viewLeadDetails.bind(this));
            $(document).on('change', '.rtbcb-status-select', this.updateLeadStatus.bind(this));
            
            // Bulk actions
            $(document).on('change', '.rtbcb-bulk-checkbox', this.toggleBulkActions.bind(this));
            $(document).on('change', '#rtbcb-select-all', this.toggleSelectAll.bind(this));
            
            // Chart controls
            $(document).on('change', '.rtbcb-chart-filter', this.updateChart.bind(this));
            
            // Search
            $(document).on('input', '.rtbcb-search-input', this.debounce(this.handleSearch.bind(this), 300));
            $(document).on('change', '.rtbcb-filter-select', this.handleFilter.bind(this));
            
            // Export actions
            $(document).on('click', '.rtbcb-export-btn', this.handleExport.bind(this));
        },

        /**
         * Initialize modal functionality
         */
        initModals() {
            // Close modal on escape key
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeModal();
                }
            });

            // Prevent modal close when clicking inside modal content
            $(document).on('click', '.rtbcb-modal', (e) => {
                e.stopPropagation();
            });
        },

        /**
         * Initialize charts
         */
        initCharts() {
            $('.rtbcb-chart').each((index, element) => {
                const $chart = $(element);
                const chartType = $chart.data('chart-type');
                const chartData = $chart.data('chart-data');
                
                if (chartType && chartData) {
                    this.createChart(element, chartType, chartData);
                }
            });
        },

        /**
         * Initialize bulk actions
         */
        initBulkActions() {
            // Hide bulk actions initially
            $('.rtbcb-bulk-actions').removeClass('active');
        },

        /**
         * Initialize search functionality
         */
        initSearch() {
            // Clear search
            $(document).on('click', '.rtbcb-clear-search', () => {
                $('.rtbcb-search-input').val('').trigger('input');
            });
        },

        /**
         * Setup global AJAX error handling
         */
        setupAjaxErrorHandling() {
            $(document).ajaxError((event, xhr, settings, thrownError) => {
                console.error('AJAX Error:', thrownError, xhr);
                this.showNotification(rtbcbAdmin.strings.error, 'error');
            });
        },

        /**
         * Handle AJAX form submissions
         */
        handleAjaxForm(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $submitBtn = $form.find('[type="submit"]');
            const originalText = $submitBtn.text();
            
            // Add loading state
            this.setButtonLoading($submitBtn, true);
            
            $.ajax({
                url: rtbcbAdmin.ajaxUrl,
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message || rtbcbAdmin.strings.success, 'success');
                        
                        // Refresh page if needed
                        if (response.data.refresh) {
                            setTimeout(() => location.reload(), 1000);
                        }
                        
                        // Close modal if form is in modal
                        if ($form.closest('.rtbcb-modal').length) {
                            this.closeModal();
                        }
                        
                        // Reset form
                        $form[0].reset();
                    } else {
                        this.showNotification(response.data || rtbcbAdmin.strings.error, 'error');
                    }
                },
                error: (xhr) => {
                    this.showNotification(rtbcbAdmin.strings.error, 'error');
                },
                complete: () => {
                    this.setButtonLoading($submitBtn, false, originalText);
                }
            });
        },

        /**
         * Handle button actions
         */
        handleButtonAction(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const action = $btn.data('action');
            const confirmMsg = $btn.data('confirm');
            
            if (confirmMsg && !confirm(confirmMsg)) {
                return;
            }
            
            switch (action) {
                case 'delete-lead':
                    this.deleteLead($btn.data('lead-id'));
                    break;
                case 'export-leads':
                    this.exportLeads();
                    break;
                case 'bulk-delete':
                    this.bulkDeleteLeads();
                    break;
                case 'refresh-analytics':
                    this.refreshAnalytics();
                    break;
                case 'test-api':
                    this.testApiConnection();
                    break;
                default:
                    console.warn('Unknown action:', action);
            }
        },

        /**
         * Open modal
         */
        openModal(e) {
            e.preventDefault();
            
            const $trigger = $(e.target);
            const modalId = $trigger.data('modal');
            const modalContent = $trigger.data('modal-content');
            
            if (modalId) {
                const $modal = $('#' + modalId);
                if ($modal.length) {
                    $modal.addClass('active');
                    $('body').addClass('rtbcb-modal-open');
                    
                    // Load dynamic content if needed
                    if (modalContent) {
                        this.loadModalContent($modal, modalContent);
                    }
                }
            }
        },

        /**
         * Close modal
         */
        closeModal() {
            $('.rtbcb-modal-overlay').removeClass('active');
            $('body').removeClass('rtbcb-modal-open');
        },

        /**
         * Load modal content dynamically
         */
        loadModalContent($modal, contentType) {
            const $body = $modal.find('.rtbcb-modal-body');
            
            $body.html('<div class="rtbcb-chart-loading"><div class="rtbcb-chart-spinner"></div></div>');
            
            $.ajax({
                url: rtbcbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rtbcb_admin_action',
                    action_type: 'get_modal_content',
                    content_type: contentType,
                    nonce: rtbcbAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $body.html(response.data.content);
                    } else {
                        $body.html('<p class="error">' + (response.data || rtbcbAdmin.strings.error) + '</p>');
                    }
                },
                error: () => {
                    $body.html('<p class="error">' + rtbcbAdmin.strings.error + '</p>');
                }
            });
        },

        /**
         * View lead details
         */
        viewLeadDetails(e) {
            e.preventDefault();
            
            const leadId = $(e.target).data('lead-id');
            
            $.ajax({
                url: rtbcbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rtbcb_admin_action',
                    action_type: 'get_lead_details',
                    lead_id: leadId,
                    nonce: rtbcbAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showLeadModal(response.data);
                    } else {
                        this.showNotification(response.data || rtbcbAdmin.strings.error, 'error');
                    }
                }
            });
        },

        /**
         * Update lead status
         */
        updateLeadStatus(e) {
            const $select = $(e.target);
            const leadId = $select.data('lead-id');
            const newStatus = $select.val();
            
            $.ajax({
                url: rtbcbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rtbcb_admin_action',
                    action_type: 'update_lead_status',
                    lead_id: leadId,
                    status: newStatus,
                    nonce: rtbcbAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data, 'success');
                        
                        // Update status badge
                        const $badge = $select.closest('tr').find('.rtbcb-status-badge');
                        $badge.removeClass().addClass(`rtbcb-status-badge rtbcb-status-${newStatus}`).text(newStatus);
                    } else {
                        this.showNotification(response.data || rtbcbAdmin.strings.error, 'error');
                        $select.val($select.data('original-value')); // Revert
                    }
                }
            });
        },

        /**
         * Toggle bulk actions visibility
         */
        toggleBulkActions() {
            const checkedCount = $('.rtbcb-bulk-checkbox:checked').length;
            const $bulkActions = $('.rtbcb-bulk-actions');
            
            if (checkedCount > 0) {
                $bulkActions.addClass('active');
                $('.rtbcb-bulk-text').text(`${checkedCount} item(s) selected`);
            } else {
                $bulkActions.removeClass('active');
            }
        },

        /**
         * Toggle select all checkboxes
         */
        toggleSelectAll(e) {
            const isChecked = $(e.target).is(':checked');
            $('.rtbcb-bulk-checkbox').prop('checked', isChecked);
            this.toggleBulkActions();
        },

        /**
         * Handle search input
         */
        handleSearch(e) {
            const query = $(e.target).val();
            this.filterTable({ search: query });
        },

        /**
         * Handle filter changes
         */
        handleFilter(e) {
            const $filter = $(e.target);
            const filterType = $filter.data('filter');
            const filterValue = $filter.val();
            
            this.filterTable({ [filterType]: filterValue });
        },

        /**
         * Filter table data
         */
        filterTable(filters) {
            // Implementation depends on whether using server-side or client-side filtering
            // For now, trigger a page refresh with filter parameters
            const url = new URL(window.location);
            
            Object.keys(filters).forEach(key => {
                if (filters[key]) {
                    url.searchParams.set(key, filters[key]);
                } else {
                    url.searchParams.delete(key);
                }
            });
            
            window.location.href = url.toString();
        },

        /**
         * Create chart
         */
        createChart(element, type, data) {
            const ctx = element.getContext('2d');
            
            const config = {
                type: type,
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            cornerRadius: 8,
                        }
                    },
                    scales: type !== 'pie' && type !== 'doughnut' ? {
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)',
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)',
                            }
                        }
                    } : {}
                }
            };
            
            return new Chart(ctx, config);
        },

        /**
         * Update chart data
         */
        updateChart(e) {
            const $control = $(e.target);
            const chartId = $control.data('chart');
            const chartType = $control.data('chart-type');
            const dateRange = $control.val();
            
            const $chartContainer = $(`#${chartId}`).parent();
            $chartContainer.find('.rtbcb-chart-loading').remove();
            $chartContainer.append('<div class="rtbcb-chart-loading"><div class="rtbcb-chart-spinner"></div></div>');
            
            $.ajax({
                url: rtbcbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rtbcb_get_analytics_data',
                    chart_type: chartType,
                    date_range: dateRange,
                    nonce: rtbcbAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateChartData(chartId, response.data);
                    } else {
                        this.showNotification(response.data || rtbcbAdmin.strings.error, 'error');
                    }
                },
                complete: () => {
                    $chartContainer.find('.rtbcb-chart-loading').remove();
                }
            });
        },

        /**
         * Update chart with new data
         */
        updateChartData(chartId, data) {
            const chart = Chart.getChart(chartId);
            if (chart) {
                chart.data = data;
                chart.update();
            }
        },

        /**
         * Delete single lead
         */
        deleteLead(leadId) {
            if (!confirm(rtbcbAdmin.strings.confirm_delete)) {
                return;
            }
            
            $.ajax({
                url: rtbcbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rtbcb_delete_leads',
                    lead_ids: [leadId],
                    nonce: rtbcbAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        $(`tr[data-lead-id="${leadId}"]`).fadeOut(() => {
                            $(this).remove();
                        });
                    } else {
                        this.showNotification(response.data || rtbcbAdmin.strings.error, 'error');
                    }
                }
            });
        },

        /**
         * Bulk delete leads
         */
        bulkDeleteLeads() {
            const leadIds = $('.rtbcb-bulk-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (leadIds.length === 0) {
                this.showNotification('No items selected.', 'warning');
                return;
            }
            
            if (!confirm(rtbcbAdmin.strings.bulk_confirm)) {
                return;
            }
            
            $.ajax({
                url: rtbcbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rtbcb_delete_leads',
                    lead_ids: leadIds,
                    nonce: rtbcbAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        location.reload(); // Refresh to update counts
                    } else {
                        this.showNotification(response.data || rtbcbAdmin.strings.error, 'error');
                    }
                }
            });
        },

        /**
         * Export leads
         */
        exportLeads() {
            const $btn = $('.rtbcb-export-btn');
            this.setButtonLoading($btn, true);
            
            // Create form and submit to trigger download
            const $form = $('<form>', {
                method: 'POST',
                action: rtbcbAdmin.ajaxUrl
            });
            
            $form.append($('<input>', { type: 'hidden', name: 'action', value: 'rtbcb_export_leads' }));
            $form.append($('<input>', { type: 'hidden', name: 'nonce', value: rtbcbAdmin.nonce }));
            
            // Add selected filters
            $('.rtbcb-filter-select').each(function() {
                const $filter = $(this);
                if ($filter.val()) {
                    $form.append($('<input>', { 
                        type: 'hidden', 
                        name: $filter.attr('name'), 
                        value: $filter.val() 
                    }));
                }
            });
            
            $form.appendTo('body').submit().remove();
            
            setTimeout(() => {
                this.setButtonLoading($btn, false);
            }, 2000);
        },

        /**
         * Handle export button clicks
         */
        handleExport(e) {
            e.preventDefault();
            const exportType = $(e.target).data('export');
            
            switch (exportType) {
                case 'csv':
                    this.exportLeads();
                    break;
                case 'analytics':
                    this.exportAnalytics();
                    break;
                default:
                    console.warn('Unknown export type:', exportType);
            }
        },

        /**
         * Show lead details modal
         */
        showLeadModal(leadData) {
            let modalHtml = `
                <div class="rtbcb-modal-overlay active">
                    <div class="rtbcb-modal">
                        <div class="rtbcb-modal-header">
                            <h3 class="rtbcb-modal-title">Lead Details</h3>
                            <button class="rtbcb-modal-close">&times;</button>
                        </div>
                        <div class="rtbcb-modal-body">
                            <div class="rtbcb-lead-details">
                                <div class="rtbcb-detail-group">
                                    <label>Email:</label>
                                    <span>${leadData.email}</span>
                                </div>
                                <div class="rtbcb-detail-group">
                                    <label>Company Size:</label>
                                    <span>${leadData.company_size || 'N/A'}</span>
                                </div>
                                <div class="rtbcb-detail-group">
                                    <label>Industry:</label>
                                    <span>${leadData.industry || 'N/A'}</span>
                                </div>
                                <div class="rtbcb-detail-group">
                                    <label>ROI Range:</label>
                                    <span>$${leadData.roi_low} - $${leadData.roi_high}</span>
                                </div>
                                <div class="rtbcb-detail-group">
                                    <label>Created:</label>
                                    <span>${leadData.created_at}</span>
                                </div>
                            </div>
                        </div>
                        <div class="rtbcb-modal-footer">
                            <button class="rtbcb-btn rtbcb-btn-secondary rtbcb-modal-close">Close</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
        },

        /**
         * Set button loading state
         */
        setButtonLoading($btn, loading, originalText = null) {
            if (loading) {
                $btn.addClass('loading').prop('disabled', true);
                if (!$btn.data('original-text')) {
                    $btn.data('original-text', $btn.text());
                }
                $btn.text(rtbcbAdmin.strings.processing);
            } else {
                $btn.removeClass('loading').prop('disabled', false);
                $btn.text(originalText || $btn.data('original-text') || $btn.text());
            }
        },

        /**
         * Show notification
         */
        showNotification(message, type = 'info') {
            // Create notification element
            const $notification = $(`
                <div class="rtbcb-notification rtbcb-notification-${type}">
                    <div class="rtbcb-notification-content">
                        <span class="rtbcb-notification-message">${message}</span>
                        <button class="rtbcb-notification-close">&times;</button>
                    </div>
                </div>
            `);
            
            // Add to page
            if (!$('.rtbcb-notifications').length) {
                $('body').append('<div class="rtbcb-notifications"></div>');
            }
            
            $('.rtbcb-notifications').append($notification);
            
            // Auto remove
            setTimeout(() => {
                $notification.fadeOut(() => $notification.remove());
            }, 5000);
            
            // Manual close
            $notification.find('.rtbcb-notification-close').on('click', () => {
                $notification.fadeOut(() => $notification.remove());
            });
        },

        /**
         * Debounce function for search
         */
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * Refresh analytics data
         */
        refreshAnalytics() {
            $('.rtbcb-chart-container').each((index, container) => {
                const $container = $(container);
                const $chart = $container.find('canvas');
                const chartType = $chart.data('chart-type');
                
                if (chartType) {
                    $container.append('<div class="rtbcb-chart-loading"><div class="rtbcb-chart-spinner"></div></div>');
                    
                    $.ajax({
                        url: rtbcbAdmin.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'rtbcb_get_analytics_data',
                            chart_type: chartType,
                            nonce: rtbcbAdmin.nonce
                        },
                        success: (response) => {
                            if (response.success) {
                                this.updateChartData($chart.attr('id'), response.data);
                            }
                        },
                        complete: () => {
                            $container.find('.rtbcb-chart-loading').remove();
                        }
                    });
                }
            });
        },

        /**
         * Test API connection
         */
        testApiConnection() {
            const $btn = $('.rtbcb-test-api-btn');
            this.setButtonLoading($btn, true);
            
            $.ajax({
                url: rtbcbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rtbcb_admin_action',
                    action_type: 'test_api_connection',
                    nonce: rtbcbAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('API connection successful!', 'success');
                    } else {
                        this.showNotification(response.data || 'API connection failed.', 'error');
                    }
                },
                complete: () => {
                    this.setButtonLoading($btn, false);
                }
            });
        }
    };

    // Add notification styles if not already present
    const notificationStyles = `
        <style>
        .rtbcb-notifications {
            position: fixed;
            top: 32px;
            right: 20px;
            z-index: 100001;
            max-width: 400px;
        }
        .rtbcb-notification {
            background: white;
            border-left: 4px solid #3b82f6;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 12px;
            animation: rtbcb-slide-in 0.3s ease-out;
        }
        .rtbcb-notification-success { border-left-color: #10b981; }
        .rtbcb-notification-error { border-left-color: #ef4444; }
        .rtbcb-notification-warning { border-left-color: #f59e0b; }
        .rtbcb-notification-content {
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .rtbcb-notification-message {
            flex: 1;
            font-size: 14px;
            color: #1f2937;
        }
        .rtbcb-notification-close {
            background: none;
            border: none;
            font-size: 18px;
            color: #9ca3af;
            cursor: pointer;
            margin-left: 12px;
        }
        @keyframes rtbcb-slide-in {
            from { opacity: 0; transform: translateX(100%); }
            to { opacity: 1; transform: translateX(0); }
        }
        </style>
    `;
    
    if (!$('#rtbcb-notification-styles').length) {
        $('head').append(notificationStyles.replace('<style>', '<style id="rtbcb-notification-styles">'));
    }

    // Expose Admin object
    window.RTBCBAdmin = Admin;

    // Initialize when document ready
    $(document).ready(() => {
        Admin.init();
    });

})(jQuery);