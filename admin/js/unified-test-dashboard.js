/**
 * Unified Test Dashboard JavaScript
 * Handles all dashboard functionality including company overview testing,
 * progress tracking, debug information, and result display.
 */
(function($) {
    'use strict';

    // Dashboard state management
    const Dashboard = {
        currentTab: 'company-overview',
        isGenerating: false,
        progressTimer: null,
        startTime: null,
        currentRequest: null,

        // Initialize dashboard
        init() {
            this.bindEvents();
            this.initializeTabs();
            this.checkSystemStatus();
        },

        // Bind all event handlers
        bindEvents() {
            // Tab navigation
            $('.rtbcb-test-tabs .nav-tab').on('click', this.handleTabClick.bind(this));

            // Company overview controls
            $('#generate-company-overview').on('click', this.generateCompanyOverview.bind(this));
            $('#clear-results').on('click', this.clearResults.bind(this));
            $('#export-results').on('click', this.exportResults.bind(this));
            $('#copy-results').on('click', this.copyResults.bind(this));
            $('#regenerate-results').on('click', this.regenerateResults.bind(this));
            $('#retry-request').on('click', this.retryRequest.bind(this));

            // Debug controls
            $('#show-debug-info').on('change', this.toggleDebugInfo.bind(this));
            $('#toggle-debug').on('click', this.toggleDebugPanel.bind(this));

            // Real-time input validation
            $('#company-name-input').on('input', this.validateInput.bind(this));

            // Keyboard shortcuts
            $(document).on('keydown', this.handleKeyboardShortcuts.bind(this));
        },

        // Initialize tab system
        initializeTabs() {
            // Set initial active tab
            this.switchTab('company-overview');
        },

        // Handle tab switching
        handleTabClick(e) {
            e.preventDefault();
            const tab = $(e.currentTarget).data('tab');
            this.switchTab(tab);
        },

        // Switch to specific tab
        switchTab(tabName) {
            if (this.isGenerating) {
                this.showNotification('Cannot switch tabs while generation is in progress', 'warning');
                return;
            }

            // Update tab navigation
            $('.rtbcb-test-tabs .nav-tab').removeClass('nav-tab-active');
            $(`.rtbcb-test-tabs .nav-tab[data-tab="${tabName}"]`).addClass('nav-tab-active');

            // Show/hide sections
            $('.rtbcb-test-section').removeClass('active').hide();
            $(`#${tabName}`).addClass('active').show();

            this.currentTab = tabName;
        },

        // Check system status on load
        checkSystemStatus() {
            const apiKey = $('#rtbcb_openai_api_key').length;
            const companyData = $('.rtbcb-status-indicator').hasClass('status-good');

            if (!apiKey) {
                this.showNotification('OpenAI API key is not configured. Please configure it in Settings.', 'error');
                $('#generate-company-overview').prop('disabled', true);
            }
        },

        // Validate input fields
        validateInput() {
            const companyName = $('#company-name-input').val().trim();
            const isValid = companyName.length >= 2;

            $('#generate-company-overview').prop('disabled', !isValid || this.isGenerating);

            if (companyName.length > 0 && companyName.length < 2) {
                $('#company-name-input').addClass('error');
            } else {
                $('#company-name-input').removeClass('error');
            }
        },

        // Generate company overview with comprehensive tracking
        generateCompanyOverview() {
            if (this.isGenerating) return;

            const companyName = $('#company-name-input').val().trim();
            const model = $('#model-selection').val();
            const showDebug = $('#show-debug-info').is(':checked');

            if (!companyName) {
                this.showNotification('Please enter a company name', 'error');
                return;
            }

            this.startGeneration(companyName, model, showDebug);
        },

        // Start the generation process
        startGeneration(companyName, model, showDebug) {
            this.isGenerating = true;
            this.startTime = Date.now();

            // Update UI state
            this.setLoadingState(true);
            this.showProgressContainer();
            this.hideContainers(['results', 'error']);

            if (showDebug) {
                this.showDebugPanel();
            }

            // Start progress tracking
            this.startProgressTracking();

            // Prepare request data
            const requestData = {
                action: 'rtbcb_test_company_overview_enhanced',
                nonce: $('#rtbcb_unified_test_nonce').val(),
                company_name: companyName,
                model: model,
                show_debug: showDebug,
                request_id: this.generateRequestId()
            };

            // Make AJAX request
            this.currentRequest = $.ajax({
                url: $('#ajaxurl').val(),
                type: 'POST',
                data: requestData,
                timeout: 120000, // 2 minutes timeout

                success: (response) => {
                    this.handleGenerationSuccess(response);
                },

                error: (xhr, status, error) => {
                    this.handleGenerationError(xhr, status, error);
                },

                complete: () => {
                    this.completeGeneration();
                }
            });
        },

        // Handle successful generation
        handleGenerationSuccess(response) {
            if (response.success) {
                const data = response.data;

                // Display results
                this.showResults(data);

                // Update debug info if enabled
                if ($('#show-debug-info').is(':checked')) {
                    this.updateDebugInfo(data.debug || {});
                }

                // Show success notification
                this.showNotification('Company overview generated successfully!', 'success');

            } else {
                this.handleGenerationError(null, 'server_error', response.data?.message || 'Unknown server error');
            }
        },

        // Handle generation errors
        handleGenerationError(xhr, status, error) {
            let errorMessage = 'An error occurred while generating the overview';
            let debugInfo = {};

            if (status === 'timeout') {
                errorMessage = 'Request timed out. The generation is taking too long.';
            } else if (xhr && xhr.responseJSON) {
                errorMessage = xhr.responseJSON.data?.message || errorMessage;
                debugInfo = xhr.responseJSON.data?.debug || {};
            } else if (error) {
                errorMessage = error;
            }

            this.showError(errorMessage, debugInfo);
            this.showNotification(errorMessage, 'error');
        },

        // Complete generation process
        completeGeneration() {
            this.isGenerating = false;
            this.setLoadingState(false);
            this.stopProgressTracking();
            this.currentRequest = null;
        },

        // Show results in the results container
        showResults(data) {
            const resultsContainer = $('#results-container');
            const resultsContent = $('#results-content');
            const resultsMeta = $('#results-meta');

            // Display main content
            resultsContent.html(this.formatContent(data.overview || data.content || 'No content available'));

            // Display metadata
            const meta = this.buildMetaInfo(data);
            resultsMeta.html(meta);

            // Show container and enable actions
            resultsContainer.show().addClass('rtbcb-fade-in');
            $('#export-results, #copy-results, #regenerate-results').prop('disabled', false);

            // Hide progress
            this.hideProgressContainer();
        },

        // Show error information
        showError(message, debugInfo = {}) {
            const errorContainer = $('#error-container');
            const errorContent = $('#error-content');
            const errorDebug = $('#error-debug');

            errorContent.html(`<strong>Error:</strong> ${message}`);

            if (Object.keys(debugInfo).length > 0) {
                errorDebug.html(JSON.stringify(debugInfo, null, 2)).show();
            } else {
                errorDebug.hide();
            }

            errorContainer.show().addClass('rtbcb-fade-in');
            this.hideProgressContainer();
        },

        // Progress tracking system
        startProgressTracking() {
            let progress = 0;
            const steps = [
                'Initializing request...',
                'Connecting to OpenAI API...',
                'Sending company analysis prompt...',
                'Processing AI response...',
                'Analyzing company data...',
                'Generating insights...',
                'Finalizing overview...'
            ];

            let stepIndex = 0;

            const updateProgress = () => {
                if (!this.isGenerating) return;

                progress = Math.min(progress + Math.random() * 15, 95);

                // Update progress bar
                $('#progress-fill').css('width', `${progress}%`);

                // Update status message
                if (stepIndex < steps.length && progress > (stepIndex + 1) * 12) {
                    $('#progress-status').text(steps[stepIndex]);
                    stepIndex++;
                }

                // Update timer
                this.updateTimer();

                if (this.isGenerating) {
                    setTimeout(updateProgress, 800 + Math.random() * 400);
                }
            };

            updateProgress();
        },

        // Update the progress timer
        updateTimer() {
            if (this.startTime) {
                const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
                const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
                const seconds = (elapsed % 60).toString().padStart(2, '0');
                $('#progress-timer').text(`${minutes}:${seconds}`);
            }
        },

        // Stop progress tracking
        stopProgressTracking() {
            $('#progress-fill').css('width', '100%');
            $('#progress-status').text('Complete!');

            setTimeout(() => {
                this.hideProgressContainer();
            }, 1000);
        },

        // Debug information management
        showDebugPanel() {
            $('#debug-panel').show().addClass('rtbcb-slide-down');
        },

        hideDebugPanel() {
            $('#debug-panel').hide();
        },

        toggleDebugInfo() {
            const show = $('#show-debug-info').is(':checked');
            if (show) {
                this.showDebugPanel();
            } else {
                this.hideDebugPanel();
            }
        },

        toggleDebugPanel() {
            const content = $('.rtbcb-debug-content');
            content.toggle();
        },

        updateDebugInfo(debugData) {
            if (debugData.system_prompt) {
                $('#system-prompt').text(debugData.system_prompt);
            }

            if (debugData.user_prompt) {
                $('#user-prompt').text(debugData.user_prompt);
            }

            if (debugData.api_request) {
                $('#api-request').text(JSON.stringify(debugData.api_request, null, 2));
            }

            // Update performance metrics
            $('#response-time').text(debugData.response_time || '--');
            $('#tokens-used').text(debugData.tokens_used || '--');
            $('#word-count').text(debugData.word_count || '--');
        },

        // UI state management
        setLoadingState(loading) {
            const container = $('.rtbcb-test-panel');
            const button = $('#generate-company-overview');

            if (loading) {
                container.addClass('rtbcb-loading');
                button.html('<span class="dashicons dashicons-update rtbcb-pulse"></span> Generating...');
            } else {
                container.removeClass('rtbcb-loading');
                button.html('<span class="dashicons dashicons-update"></span> Generate Overview');
            }
        },

        showProgressContainer() {
            $('#progress-container').show().addClass('rtbcb-slide-down');
        },

        hideProgressContainer() {
            $('#progress-container').hide();
        },

        hideContainers(types) {
            types.forEach(type => {
                $(`#${type}-container`).hide();
            });
        },

        // Utility functions
        formatContent(content) {
            if (!content) return '';

            // Basic formatting for readability
            return content
                .replace(/\n\n/g, '</p><p>')
                .replace(/\n/g, '<br>')
                .replace(/^/, '<p>')
                .replace(/$/, '</p>');
        },

        buildMetaInfo(data) {
            const meta = [];

            if (data.word_count) {
                meta.push(`<div><strong>Word Count:</strong> ${data.word_count}</div>`);
            }

            if (data.elapsed) {
                meta.push(`<div><strong>Generation Time:</strong> ${data.elapsed}s</div>`);
            }

            if (data.generated) {
                meta.push(`<div><strong>Generated:</strong> ${data.generated}</div>`);
            }

            if (data.model_used) {
                meta.push(`<div><strong>Model:</strong> ${data.model_used}</div>`);
            }

            return meta.join('');
        },

        generateRequestId() {
            return 'req_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },

        // Action handlers
        clearResults() {
            this.hideContainers(['results', 'error', 'progress']);
            this.hideDebugPanel();
            $('#company-name-input').val('').focus();
            $('#export-results, #copy-results, #regenerate-results').prop('disabled', true);
            this.showNotification('Results cleared', 'info');
        },

        regenerateResults() {
            if ($('#company-name-input').val().trim()) {
                this.generateCompanyOverview();
            } else {
                this.showNotification('Please enter a company name first', 'warning');
            }
        },

        retryRequest() {
            this.hideContainers(['error']);
            this.generateCompanyOverview();
        },

        exportResults() {
            const content = $('#results-content').text();
            const meta = $('#results-meta').text();
            const companyName = $('#company-name-input').val().trim();

            if (!content) {
                this.showNotification('No results to export', 'warning');
                return;
            }

            const exportData = {
                company: companyName,
                content: content,
                meta: meta,
                exported_at: new Date().toISOString(),
                dashboard_version: '1.0'
            };

            this.downloadJSON(exportData, `${companyName}_overview_${Date.now()}.json`);
            this.showNotification('Results exported successfully', 'success');
        },

        copyResults() {
            const content = $('#results-content').text();

            if (!content) {
                this.showNotification('No results to copy', 'warning');
                return;
            }

            this.copyToClipboard(content)
                .then(() => {
                    this.showNotification('Results copied to clipboard', 'success');
                })
                .catch(() => {
                    this.showNotification('Failed to copy to clipboard', 'error');
                });
        },

        // Utility methods
        copyToClipboard(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                return navigator.clipboard.writeText(text);
            }

            return new Promise((resolve, reject) => {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();

                try {
                    const successful = document.execCommand('copy');
                    document.body.removeChild(textarea);
                    if (successful) {
                        resolve();
                    } else {
                        reject(new Error('Copy command failed'));
                    }
                } catch (err) {
                    document.body.removeChild(textarea);
                    reject(err);
                }
            });
        },

        downloadJSON(data, filename) {
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            URL.revokeObjectURL(url);
        },

        showNotification(message, type = 'info') {
            // Create notification element
            const notification = $(`
                <div class="rtbcb-notification rtbcb-${type}">
                    <span class="dashicons dashicons-${this.getNotificationIcon(type)}"></span>
                    <span class="message">${message}</span>
                    <button class="dismiss">&times;</button>
                </div>
            `);

            // Add to page
            $('body').append(notification);

            // Position and show
            notification.css({
                position: 'fixed',
                top: '32px',
                right: '20px',
                zIndex: 999999,
                maxWidth: '400px',
                padding: '12px 16px',
                borderRadius: '4px',
                boxShadow: '0 2px 8px rgba(0,0,0,0.15)',
                display: 'flex',
                alignItems: 'center',
                gap: '8px',
                fontSize: '14px',
                fontWeight: '500'
            }).addClass('rtbcb-fade-in');

            // Auto dismiss
            setTimeout(() => {
                notification.fadeOut(300, () => notification.remove());
            }, 5000);

            // Manual dismiss
            notification.find('.dismiss').on('click', () => {
                notification.fadeOut(300, () => notification.remove());
            });
        },

        getNotificationIcon(type) {
            const icons = {
                success: 'yes-alt',
                error: 'dismiss',
                warning: 'warning',
                info: 'info-outline'
            };
            return icons[type] || 'info-outline';
        },

        handleKeyboardShortcuts(e) {
            // Ctrl/Cmd + Enter to generate
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                if (this.currentTab === 'company-overview' && !this.isGenerating) {
                    e.preventDefault();
                    this.generateCompanyOverview();
                }
            }

            // Escape to cancel generation
            if (e.key === 'Escape' && this.isGenerating) {
                e.preventDefault();
                if (this.currentRequest) {
                    this.currentRequest.abort();
                    this.showNotification('Generation cancelled', 'info');
                }
            }
        }
    };

    // Initialize when DOM is ready
    $(document).ready(() => {
        Dashboard.init();
    });

    // Expose Dashboard object for debugging
    window.RTBCBDashboard = Dashboard;

})(jQuery);

