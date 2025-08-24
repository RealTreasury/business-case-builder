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
        apiResults: {},
        ragResults: [],
        ragContextText: '',
        useRagContext: false,
        ragRequest: null,

        // Initialize dashboard
        init: function() {
            this.bindEvents();
            this.initializeTabs();
            this.checkSystemStatus();
            this.initApiHealth();
            this.initRagModule();
            this.initLLMModule();
            this.setupCharts();

            // Store default button text for state management
            $('[data-action]').each(function() {
                $(this).data('default-text', $(this).html());
            });

            // Re-init after tab switches
            $(document).on('rtbcb:tab-switched', this.reinitializeCurrentTab.bind(this));
        },

        reinitializeCurrentTab: function() {
            // Rebind any dynamic content in the active tab
            const activeTab = this.currentTab;

            switch(activeTab) {
                case 'llm-tests':
                    this.initLLMModule();
                    break;
                case 'rag-system':
                    this.initRagModule();
                    break;
                case 'api-health':
                    this.initApiHealth();
                    break;
            }
        },

        initLLMModule: function() {
            if (typeof this.initLLMIntegration === 'function') {
                this.initLLMIntegration();
            }
        },

        setupCharts: function() {
            if (typeof this.setupLLMCharts === 'function') {
                this.setupLLMCharts();
            }
        },

        // Bind all event handlers
        bindEvents() {
            // Tab navigation
            $('.rtbcb-test-tabs .nav-tab').on('click', this.handleTabClick.bind(this));

            // Delegated action handlers
            $(document).on('click', '[data-action="run-company-overview"]', this.generateCompanyOverview.bind(this));
            $(document).on('click', '[data-action="run-llm-test"]', this.runLLMTest.bind(this));
            $(document).on('click', '[data-action="run-rag-test"]', this.runRAGTest.bind(this));
            $(document).on('click', '[data-action="api-health-ping"]', this.runAPIHealthPing.bind(this));
            $(document).on('click', '[data-action="export-results"]', this.exportResults.bind(this));

            // Company overview controls
            $('#clear-results').on('click', this.clearResults.bind(this));
            $('#copy-results').on('click', this.copyResults.bind(this));
            $('#regenerate-results').on('click', this.regenerateResults.bind(this));
            $('#retry-request').on('click', this.retryRequest.bind(this));

            // Debug controls
            $('#show-debug-info').on('change', this.toggleDebugInfo.bind(this));
            $('#toggle-debug').on('click', this.toggleDebugPanel.bind(this));

            // API health controls
            $('.rtbcb-api-health-table').on('click', '.rtbcb-retest', (e) => {
                const comp = $(e.currentTarget).data('component');
                this.runSingleApiTest(comp);
            });
            $('.rtbcb-api-health-table').on('click', '.rtbcb-view-details', (e) => {
                const comp = $(e.currentTarget).data('component');
                this.toggleApiDetails(comp);
            });

            // Data health controls
            $('#rtbcb-run-data-health').on('click', this.runDataHealthChecks.bind(this));

            // Settings controls
            $('#rtbcb-dashboard-settings-form').on('submit', this.saveDashboardSettings.bind(this));

            // RAG testing controls
            $('#rtbcb-rag-rebuild').on('click', this.rebuildRagIndex.bind(this));
            $('#rtbcb-rag-query').on('input', this.validateRagQuery.bind(this));
            $('#rtbcb-copy-rag-context').on('click', this.copyRagContext.bind(this));
            $('#rtbcb-export-rag-results').on('click', this.exportRagResults.bind(this));
            $('#rtbcb-rag-cancel').on('click', this.cancelRagQuery.bind(this));
            $('#rtbcb-rag-use-context').on('change', (e) => { this.useRagContext = e.target.checked; });

            // Report preview controls
            $('#rtbcb-generate-preview-report').on('click', this.generatePreviewReport.bind(this));

            // Real-time input validation
            $('#company-name-input').on('input', this.validateInput.bind(this));

            // Keyboard shortcuts
            $(document).on('keydown', this.handleKeyboardShortcuts.bind(this));
        },

        // Initialize tab system
        initializeTabs() {
            const hash = window.location.hash.replace('#', '');
            const validTabs = ['company-overview','roi-calculator','llm-tests','rag-system','api-health','data-health','report-preview','settings'];
            if (validTabs.includes(hash)) {
                this.switchTab(hash);
            } else {
                this.switchTab('company-overview');
            }
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
            $(document).trigger('rtbcb:tab-switched', [tabName]);
        },

        // Check system status on load
        checkSystemStatus() {
            const apiKey = $('#rtbcb_openai_api_key').val().trim();
            const companyData = $('.rtbcb-status-indicator').hasClass('status-good');

            if (!apiKey) {
                this.showNotification('OpenAI API key is not configured. Please configure it in Settings.', 'error');
                $('[data-action="run-company-overview"]').prop('disabled', true);
            }
        },

        // Validate input fields
        validateInput() {
            const companyName = $('#company-name-input').val().trim();
            const isValid = companyName.length >= 2;

            $('[data-action="run-company-overview"]').prop('disabled', !isValid || this.isGenerating);

            if (companyName.length > 0 && companyName.length < 2) {
                $('#company-name-input').addClass('error');
            } else {
                $('#company-name-input').removeClass('error');
            }
        },

        // Standardized button state management
        setButtonState: function(selector, state, text) {
            const $button = $(selector);

            switch(state) {
                case 'loading':
                    $button.prop('disabled', true)
                           .attr('aria-busy', 'true')
                           .addClass('rtbcb-loading')
                           .html(`<span class="dashicons dashicons-update rtbcb-spin"></span> ${text || 'Loading...'}`);
                    break;

                case 'success':
                    $button.prop('disabled', false)
                           .attr('aria-busy', 'false')
                           .removeClass('rtbcb-loading')
                           .addClass('rtbcb-success')
                           .html(`<span class="dashicons dashicons-yes-alt"></span> ${text || 'Complete'}`);
                    setTimeout(() => $button.removeClass('rtbcb-success'), 3000);
                    break;

                case 'error':
                    $button.prop('disabled', false)
                           .attr('aria-busy', 'false')
                           .removeClass('rtbcb-loading')
                           .addClass('rtbcb-error')
                           .html(`<span class="dashicons dashicons-warning"></span> ${text || 'Error'}`);
                    setTimeout(() => $button.removeClass('rtbcb-error'), 5000);
                    break;

                case 'ready':
                default:
                    $button.prop('disabled', false)
                           .attr('aria-busy', 'false')
                           .removeClass('rtbcb-loading rtbcb-success rtbcb-error')
                           .html(text || $button.data('default-text') || 'Run Test');
                    break;
            }
        },

        // Centralized AJAX request handler with retry logic
        request: function(action, data = {}, options = {}) {
            const defaults = {
                retries: 3,
                backoffMs: 1000,
                validateResponse: true
            };

            const config = Object.assign({}, defaults, options);

            return new Promise((resolve, reject) => {
                const attemptRequest = (attemptNum) => {
                    // Ensure nonce is present
                    if (!rtbcbDashboard.nonces[action]) {
                        reject(new Error(`Missing nonce for action: ${action}`));
                        return;
                    }

                    const requestData = Object.assign({
                        action: `rtbcb_${action}`,
                        nonce: rtbcbDashboard.nonces[action]
                    }, data);

                    $.ajax({
                        url: rtbcbDashboard.ajaxurl,
                        type: 'POST',
                        data: requestData,
                        timeout: 60000,

                        success: (response) => {
                            if (config.validateResponse && (!response || !response.hasOwnProperty('success'))) {
                                reject(new Error('Invalid response format'));
                                return;
                            }

                            if (response.success) {
                                resolve(response.data || response);
                            } else {
                                reject(new Error(response.data?.message || 'Request failed'));
                            }
                        },

                        error: (xhr, status, error) => {
                            if (xhr.status === 429 && attemptNum < config.retries) {
                                // Rate limited - retry with exponential backoff
                                const delay = config.backoffMs * Math.pow(2, attemptNum - 1);
                                setTimeout(() => attemptRequest(attemptNum + 1), delay);
                                return;
                            }

                            if (attemptNum < config.retries) {
                                setTimeout(() => attemptRequest(attemptNum + 1), config.backoffMs);
                                return;
                            }

                            reject(new Error(`${status}: ${error}`));
                        }
                    });
                };

                attemptRequest(1);
            });
        },

        // Chart.js Memory Management
        destroyChart: function(chartId) {
            if (this.charts && this.charts[chartId]) {
                this.charts[chartId].destroy();
                delete this.charts[chartId];
            }
        },

        createChart: function(chartId, config) {
            this.destroyChart(chartId);

            if (!this.charts) this.charts = {};

            const ctx = document.getElementById(chartId);
            if (!ctx) {
                console.warn(`Chart canvas ${chartId} not found`);
                return null;
            }

            this.charts[chartId] = new Chart(ctx, config);
            return this.charts[chartId];
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

        runLLMTest: function(e) {
            this.runModelComparison(e);
        },

        runRAGTest: function(e) {
            this.runRagQuery(e);
        },

        runAPIHealthPing: function(e) {
            this.runAllApiTests(e);
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
                nonce: rtbcbDashboard.nonces.dashboard,
                company_name: companyName,
                model: model,
                show_debug: showDebug,
                request_id: this.generateRequestId()
            };

            // Make AJAX request
            this.currentRequest = $.ajax({
                url: rtbcbDashboard.ajaxurl,
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
            $('[data-action="export-results"], #copy-results, #regenerate-results').prop('disabled', false);

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
        setLoadingState(loading, buttonSelector = '[data-action="run-company-overview"]', text = loading ? 'Generating...' : 'Generate Overview') {
            const container = $('.rtbcb-test-panel');

            if (loading) {
                container.addClass('rtbcb-loading');
                this.setButtonState(buttonSelector, 'loading', text);
            } else {
                container.removeClass('rtbcb-loading');
                this.setButtonState(buttonSelector, 'ready', text);
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
            $('[data-action="export-results"], #copy-results, #regenerate-results').prop('disabled', true);
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

    // LLM Integration Testing Module
    Object.assign(Dashboard, {
        // LLM Integration state
        llmData: null,
        llmCharts: {},
        currentLLMMode: 'model-comparison',
        llmTestInProgress: false,
        promptTemplates: {
            'company-analysis': {
                system: 'You are a business analyst specializing in treasury operations. Provide detailed company analysis based on the information given.',
                user: 'Analyze the following company: {{company_name}}. Focus on their treasury operations, financial structure, and technology needs.'
            },
            'business-case': {
                system: 'You are a treasury consultant helping companies build business cases for technology investments.',
                user: 'Create a compelling business case for treasury technology investment for {{company_name}} in the {{industry}} industry.'
            },
            'roi-explanation': {
                system: 'You are a financial analyst explaining ROI calculations in simple terms.',
                user: 'Explain the ROI calculation and benefits of treasury technology for a company with {{metrics}}.'
            },
            'industry-insights': {
                system: 'You are an industry expert providing insights on treasury technology trends.',
                user: 'Provide current treasury technology insights for the {{industry}} industry, focusing on key trends and challenges.'
            }
        },

        // Initialize LLM Integration Module
        initLLMIntegration() {
            this.bindLLMEvents();
            this.setupLLMCharts();
            this.loadPromptTemplates();
        },

        // Bind LLM Integration events
        bindLLMEvents() {
            // Mode switching
            $('.rtbcb-mode-tab').on('click', this.handleLLMModeSwitch.bind(this));

            // Model comparison controls
            $('#llm-test-scenario').on('change', this.handleScenarioChange.bind(this));
            $('#llm-temperature').on('input', this.updateTemperatureDisplay.bind(this));
            $('#load-prompt-template').on('click', this.loadPromptTemplate.bind(this));
            $('#save-prompt-template').on('click', this.savePromptTemplate.bind(this));
            $('#export-llm-comparison').on('click', this.exportLLMResults.bind(this));

            // Response detail toggles
            $('#toggle-comparison-details').on('click', this.toggleComparisonDetails.bind(this));
            $('#rate-responses').on('click', this.showResponseRating.bind(this));

            // Prompt engineering controls
            $('#add-prompt-variant').on('click', this.addPromptVariant.bind(this));
            $(document).on('click', '.rtbcb-remove-variant', this.removePromptVariant.bind(this));
            $(document).on('input', '.rtbcb-variant-temperature', this.updateVariantTemperatureDisplay.bind(this));
            $('#test-prompt-variants').on('click', this.testPromptVariants.bind(this));

            // Response evaluation controls
            $('#evaluate-response').on('click', this.evaluateResponse.bind(this));
            $('#compare-with-reference').on('click', this.compareWithReference.bind(this));

            // Token optimization controls
            $('#analyze-tokens').on('click', this.analyzeTokens.bind(this));
            $('#optimize-prompt').on('click', this.optimizePrompt.bind(this));

            // Real-time input validation
            $('#llm-user-prompt').on('input', this.validateLLMInputs.bind(this));
        },

        // Handle LLM mode switching
        handleLLMModeSwitch(e) {
            if (this.llmTestInProgress) {
                this.showNotification('Cannot switch modes while test is in progress', 'warning');
                return;
            }

            const mode = $(e.currentTarget).data('mode');
            this.switchLLMMode(mode);
        },

        // Switch to specific LLM mode
        switchLLMMode(mode) {
            // Update tab navigation
            $('.rtbcb-mode-tab').removeClass('active');
            $(`.rtbcb-mode-tab[data-mode="${mode}"]`).addClass('active');

            // Show/hide mode content
            $('.rtbcb-llm-mode-content').removeClass('active').hide();
            $(`#${mode}-mode`).addClass('active').show();

            this.currentLLMMode = mode;

            // Initialize mode-specific functionality
            switch (mode) {
                case 'model-comparison':
                    this.initModelComparisonMode();
                    break;
                case 'prompt-engineering':
                    this.initPromptEngineeringMode();
                    break;
                case 'response-evaluation':
                    this.initResponseEvaluationMode();
                    break;
                case 'token-optimization':
                    this.initTokenOptimizationMode();
                    break;
            }
        },

        // Initialize model comparison mode
        initModelComparisonMode() {
            this.validateLLMInputs();
            if (this.llmCharts.performance) {
                this.llmCharts.performance.resize();
            }
        },

        // Handle scenario change
        handleScenarioChange() {
            const scenario = $('#llm-test-scenario').val();
            if (scenario !== 'custom' && this.promptTemplates[scenario]) {
                const template = this.promptTemplates[scenario];
                $('#llm-system-prompt').val(template.system);
                $('#llm-user-prompt').val(template.user);
            }
        },

        // Update temperature display
        updateTemperatureDisplay() {
            const value = $('#llm-temperature').val();
            $('#temperature-value').text(value);
        },

        // Run model comparison
        runModelComparison() {
            if (this.llmTestInProgress) return;

            const systemPrompt = $('#llm-system-prompt').val().trim();
            const userPrompt = $('#llm-user-prompt').val().trim();
            const selectedModels = $('input[name="llm-models[]"]:checked').map(function() {
                return $(this).val();
            }).get();
            const maxTokens = parseInt($('#llm-max-tokens').val());
            const temperature = parseFloat($('#llm-temperature').val());

            if (!userPrompt) {
                this.showNotification('Please enter a user prompt', 'error');
                return;
            }

            if (selectedModels.length === 0) {
                this.showNotification('Please select at least one model to test', 'error');
                return;
            }

            let finalPrompt = userPrompt;
            if (this.useRagContext && this.ragContextText) {
                finalPrompt = `${this.ragContextText}\n\n${userPrompt}`;
            }

            this.startModelComparison({
                systemPrompt,
                userPrompt: finalPrompt,
                selectedModels,
                maxTokens,
                temperature,
                includeContext: $('#llm-include-context').is(':checked')
            });
        },

        // Start model comparison process
        startModelComparison(config) {
            this.llmTestInProgress = true;
            this.setLoadingState(true, '[data-action="run-llm-test"]', 'Testing Models...');
            this.hideContainers(['model-comparison-results']);

            // Show progress for each model
            this.showModelProgress(config.selectedModels);

            const requests = config.selectedModels.map(modelKey => {
                return this.testSingleModel(modelKey, config)
                    .then(result => ({ modelKey, result, success: true }))
                    .catch(error => ({ modelKey, error: error.message, success: false }));
            });

            Promise.allSettled(requests).then(results => {
                this.completeModelComparison(results, config);
            });
        },

        // Test single model
        testSingleModel(modelKey, config) {
            return new Promise((resolve, reject) => {
                this.updateModelProgress(modelKey, 'in-progress', 'Testing...');

                $.ajax({
                    url: rtbcbDashboard.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'rtbcb_test_llm_model',
                        nonce: rtbcbDashboard.nonces.llm,
                        model_key: modelKey,
                        system_prompt: config.systemPrompt,
                        user_prompt: config.userPrompt,
                        max_tokens: config.maxTokens,
                        temperature: config.temperature,
                        include_context: config.includeContext
                    },
                    timeout: 60000,
                    success: (response) => {
                        if (response.success) {
                            this.updateModelProgress(modelKey, 'completed', 'Completed');
                            resolve(response.data);
                        } else {
                            this.updateModelProgress(modelKey, 'error', 'Failed');
                            reject(new Error(response.data?.message || 'Test failed'));
                        }
                    },
                    error: (xhr, status, error) => {
                        this.updateModelProgress(modelKey, 'error', 'Failed');
                        reject(new Error(`Request failed: ${error}`));
                    }
                });
            });
        },

        // Show model progress
        showModelProgress(models) {
            const progressHtml = models.map(modelKey => `
                <div class="rtbcb-model-progress-item" data-model="${modelKey}">
                    <span class="dashicons dashicons-clock rtbcb-progress-icon"></span>
                    <span class="rtbcb-progress-text">${this.getModelDisplayName(modelKey)}</span>
                    <span class="rtbcb-progress-status">Waiting...</span>
                </div>
            `).join('');

            const progressContainer = $('<div class="rtbcb-model-progress"></div>').html(progressHtml);

            if ($('.rtbcb-model-progress').length) {
                $('.rtbcb-model-progress').replaceWith(progressContainer);
            } else {
                $('#model-comparison-mode .rtbcb-test-controls').after(progressContainer);
            }
        },

        // Update model progress
        updateModelProgress(modelKey, status, message) {
            const item = $(`.rtbcb-model-progress-item[data-model="${modelKey}"]`);
            item.removeClass('in-progress completed error').addClass(status);

            const icon = status === 'completed' ? 'dashicons-yes-alt' :
                        status === 'error' ? 'dashicons-dismiss' : 'dashicons-update';

            item.find('.rtbcb-progress-icon').removeClass().addClass(`dashicons ${icon} rtbcb-progress-icon`);
            item.find('.rtbcb-progress-status').text(message);
        },

        // Complete model comparison
        completeModelComparison(results, config) {
            this.llmTestInProgress = false;
            this.setLoadingState(false, '[data-action="run-llm-test"]', 'Run Model Comparison');

            const successfulResults = results.filter(r => r.value.success);
            const failedResults = results.filter(r => r.value.success === false);

            if (successfulResults.length === 0) {
                this.showNotification('All model tests failed', 'error');
                return;
            }

            if (failedResults.length > 0) {
                this.showNotification(`${failedResults.length} model(s) failed to complete`, 'warning');
            }

            // Process and display results
            this.displayModelComparisonResults(successfulResults.map(r => r.value), config);
            this.showNotification(`Model comparison completed! ${successfulResults.length} models tested.`, 'success');
        },

        // Display model comparison results
        displayModelComparisonResults(results, config) {
            this.llmData = { results, config };

            // Update summary cards
            this.updateModelSummaryCards(results);

            // Create performance chart
            this.createModelPerformanceChart(results);

            // Update response details
            this.updateResponseDetails(results);

            // Update quality analysis
            this.updateQualityAnalysis(results);

            // Show results container
            $('#model-comparison-results').show().addClass('rtbcb-fade-in');
            $('#export-llm-comparison').prop('disabled', false);

            // Remove progress indicators
            $('.rtbcb-model-progress').fadeOut(500, function() { $(this).remove(); });

            // Scroll to results
            $('#model-comparison-results')[0].scrollIntoView({ behavior: 'smooth' });
        },

        // Update model summary cards
        updateModelSummaryCards(results) {
            const cardsHtml = results.map((result, index) => {
                const modelName = this.getModelDisplayName(result.modelKey);
                const performance = this.calculateModelPerformance(result.result);
                const rating = this.getPerformanceRating(performance.score);

                return `
                    <div class="rtbcb-model-summary-card ${rating.class}">
                        <div class="rtbcb-model-card-header">
                            <h4>${modelName}</h4>
                            <div class="rtbcb-model-rating">
                                ${this.generateStarRating(rating.stars)}
                            </div>
                        </div>
                        <div class="rtbcb-model-metrics">
                            <div class="rtbcb-metric-item">
                                <span class="rtbcb-metric-label">Response Time</span>
                                <span class="rtbcb-metric-value">${result.result.response_time || 0}ms</span>
                            </div>
                            <div class="rtbcb-metric-item">
                                <span class="rtbcb-metric-label">Tokens Used</span>
                                <span class="rtbcb-metric-value">${result.result.tokens_used || 0}</span>
                            </div>
                            <div class="rtbcb-metric-item">
                                <span class="rtbcb-metric-label">Word Count</span>
                                <span class="rtbcb-metric-value">${result.result.word_count || 0}</span>
                            </div>
                            <div class="rtbcb-metric-item">
                                <span class="rtbcb-metric-label">Quality Score</span>
                                <span class="rtbcb-metric-value">${performance.score}/100</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            $('#model-summary-cards').html(cardsHtml);
        },

        // Create model performance chart
        createModelPerformanceChart(results) {
            const models = results.map(r => this.getModelDisplayName(r.modelKey));
            const responseTimes = results.map(r => r.result.response_time || 0);
            const qualityScores = results.map(r => this.calculateModelPerformance(r.result).score);

            this.llmCharts.performance = this.createChart('model-performance-chart', {
                type: 'bar',
                data: {
                    labels: models,
                    datasets: [{
                        label: 'Response Time (ms)',
                        data: responseTimes,
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        yAxisID: 'y'
                    }, {
                        label: 'Quality Score',
                        data: qualityScores,
                        type: 'line',
                        backgroundColor: 'rgba(255, 99, 132, 0.8)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 3,
                        tension: 0.3,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Response Time (ms)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Quality Score'
                            },
                            min: 0,
                            max: 100,
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });
        },

        // Update response details
        updateResponseDetails(results) {
            const detailsHtml = results.map(result => {
                const modelName = this.getModelDisplayName(result.modelKey);
                const response = result.result;

                return `
                    <div class="rtbcb-response-item">
                        <h5>${modelName} Response</h5>
                        <div class="rtbcb-response-content">${this.escapeHtml(response.content || 'No response content')}</div>
                        <div class="rtbcb-response-meta">
                            <div><strong>Tokens:</strong> ${response.tokens_used || 0}</div>
                            <div><strong>Time:</strong> ${response.response_time || 0}ms</div>
                            <div><strong>Model:</strong> ${response.model_used || 'Unknown'}</div>
                            <div><strong>Cost:</strong> $${this.calculateCost(response.tokens_used || 0, result.modelKey)}</div>
                        </div>
                    </div>
                `;
            }).join('');

            $('#model-response-grid').html(detailsHtml);
        },

        // Update quality analysis
        updateQualityAnalysis(results) {
            const metricsHtml = results.map(result => {
                const modelName = this.getModelDisplayName(result.modelKey);
                const performance = this.calculateModelPerformance(result.result);
                const rating = this.getPerformanceRating(performance.score);

                return `
                    <div class="rtbcb-quality-metric ${rating.class}">
                        <h5>${modelName}</h5>
                        <div class="rtbcb-quality-score">${performance.score}</div>
                        <div class="rtbcb-quality-description">${rating.description}</div>
                    </div>
                `;
            }).join('');

            $('#quality-metrics').html(metricsHtml);
        },

        // Toggle comparison details
        toggleComparisonDetails() {
            const $details = $('#response-details');
            const $button = $('#toggle-comparison-details');

            $details.slideToggle();

            if ($details.is(':visible')) {
                $button.find('span').text('Hide Details');
            } else {
                $button.find('span').text('Show Details');
            }
        },

        showResponseRating() {
            this.showNotification('Response rating feature coming soon!', 'info');
        },

        // Prompt Engineering Functions
        initPromptEngineeringMode() {
            this.updateVariantTemperatureDisplays();
        },

        addPromptVariant() {
            const variantCount = $('.rtbcb-variant-item').length + 1;
            const variantLetter = String.fromCharCode(64 + variantCount); // A, B, C, etc.

            const variantHtml = `
                <div class="rtbcb-variant-item" data-variant="${variantCount}">
                    <div class="rtbcb-variant-header">
                        <h5>Variant ${variantLetter}</h5>
                        <button type="button" class="rtbcb-remove-variant">×</button>
                    </div>
                    <textarea class="rtbcb-variant-prompt" placeholder="Enter prompt variant..."></textarea>
                    <div class="rtbcb-variant-settings">
                        <label>Temperature:</label>
                        <input type="range" class="rtbcb-variant-temperature" min="0" max="2" step="0.1" value="0.5" />
                        <span class="temperature-display">0.5</span>
                    </div>
                </div>
            `;

            $('.rtbcb-variants-container').append(variantHtml);
            $('.rtbcb-remove-variant').show();
        },

        removePromptVariant(e) {
            $(e.target).closest('.rtbcb-variant-item').fadeOut(300, function() {
                $(this).remove();
                // Hide remove buttons if only one variant left
                if ($('.rtbcb-variant-item').length <= 1) {
                    $('.rtbcb-remove-variant').hide();
                }
            });
        },

        updateVariantTemperatureDisplay(e) {
            const $slider = $(e.target);
            const $display = $slider.siblings('.temperature-display');
            $display.text($slider.val());
        },

        updateVariantTemperatureDisplays() {
            $('.rtbcb-variant-temperature').each(function() {
                const $slider = $(this);
                const $display = $slider.siblings('.temperature-display');
                $display.text($slider.val());
            });
        },

        testPromptVariants() {
            const variants = this.collectPromptVariants();

            if (variants.length === 0) {
                this.showNotification('Please add at least one prompt variant', 'error');
                return;
            }

            this.startPromptVariantTesting(variants);
        },

        collectPromptVariants() {
            const variants = [];
            $('.rtbcb-variant-item').each(function() {
                const prompt = $(this).find('.rtbcb-variant-prompt').val().trim();
                const temperature = parseFloat($(this).find('.rtbcb-variant-temperature').val());

                if (prompt) {
                    variants.push({
                        prompt,
                        temperature,
                        name: $(this).find('h5').text()
                    });
                }
            });
            return variants;
        },

        startPromptVariantTesting(variants) {
            // Implementation for testing prompt variants
            this.showNotification('Prompt variant testing feature coming soon!', 'info');
        },

        // Response Evaluation Functions
        initResponseEvaluationMode() {
            // Initialize response evaluation tools
        },

        evaluateResponse() {
            const response = $('#response-to-evaluate').val().trim();
            const reference = $('#reference-response').val().trim();

            if (!response) {
                this.showNotification('Please enter a response to evaluate', 'error');
                return;
            }

            this.llmTestInProgress = true;
            this.setLoadingState(true, '#evaluate-response', 'Evaluating...');

            $.ajax({
                url: rtbcbDashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rtbcb_evaluate_response_quality',
                    nonce: rtbcbDashboard.nonces.llm,
                    response_text: response,
                    reference_text: reference
                },
                success: (res) => {
                    if (res.success) {
                        this.displayQualityMetrics(res.data);
                        this.showNotification('Response evaluated successfully', 'success');
                    } else {
                        this.showNotification(res.data?.message || 'Evaluation failed', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    this.showNotification('Evaluation request failed: ' + error, 'error');
                },
                complete: () => {
                    this.llmTestInProgress = false;
                    this.setLoadingState(false, '#evaluate-response', 'Evaluate Response');
                }
            });
        },

        calculateResponseQuality(response) {
            const wordCount = response.split(/\s+/).length;
            const sentenceCount = response.split(/[.!?]+/).length - 1;
            const avgWordsPerSentence = wordCount / Math.max(sentenceCount, 1);

            // Basic quality scoring
            let score = 50; // Base score

            // Length scoring
            if (wordCount >= 100 && wordCount <= 500) score += 10;
            if (wordCount > 500) score += 5;

            // Structure scoring
            if (avgWordsPerSentence >= 10 && avgWordsPerSentence <= 25) score += 10;

            // Content indicators
            if (response.includes('ROI') || response.includes('return on investment')) score += 5;
            if (response.includes('business case') || response.includes('benefits')) score += 5;
            if (response.includes('implementation') || response.includes('strategy')) score += 5;

            return {
                score: Math.min(100, score),
                wordCount,
                sentenceCount,
                avgWordsPerSentence: Math.round(avgWordsPerSentence * 10) / 10,
                readabilityScore: this.calculateReadabilityScore(response)
            };
        },

        displayQualityMetrics(data) {
            const metrics = data.quality_metrics || data;
            const details = data.detailed_analysis || {};

            const metricsHtml = `
                <div class="rtbcb-quality-score-display">
                    <div class="rtbcb-score-main">
                        <h4>Overall Quality Score</h4>
                        <div class="rtbcb-score-circle ${this.getScoreClass(metrics.overall_score || 0)}">
                            ${metrics.overall_score || 0}/100
                        </div>
                    </div>
                    <div class="rtbcb-score-details">
                        <div class="rtbcb-score-item">
                            <span class="label">Word Count:</span>
                            <span class="value">${metrics.word_count || 0}</span>
                        </div>
                        <div class="rtbcb-score-item">
                            <span class="label">Characters:</span>
                            <span class="value">${metrics.character_count || 0}</span>
                        </div>
                        <div class="rtbcb-score-item">
                            <span class="label">Sentences:</span>
                            <span class="value">${metrics.sentence_count || 0}</span>
                        </div>
                        <div class="rtbcb-score-item">
                            <span class="label">Readability:</span>
                            <span class="value">${metrics.readability || details.readability || ''}</span>
                        </div>
                    </div>
                </div>
            `;

            $('#quality-score-display').html(metricsHtml);
        },

        compareWithReference() {
            this.showNotification('Response comparison feature coming soon!', 'info');
        },

        // Token Optimization Functions
        initTokenOptimizationMode() {
            // Initialize token optimization tools
        },

        analyzeTokens() {
            const prompt = $('#prompt-to-optimize').val().trim();

            if (!prompt) {
                this.showNotification('Please enter a prompt to analyze', 'error');
                return;
            }

            const analysis = this.performTokenAnalysis(prompt);
            this.displayTokenAnalysis(analysis);
        },

        performTokenAnalysis(prompt) {
            // Approximate token counting (GPT-like tokenization estimation)
            const wordCount = prompt.split(/\s+/).length;
            const charCount = prompt.length;
            const estimatedTokens = Math.ceil(charCount / 4); // Rough approximation

            return {
                wordCount,
                charCount,
                estimatedTokens,
                efficiency: this.calculateTokenEfficiency(prompt),
                suggestions: this.generateOptimizationSuggestions(prompt)
            };
        },

        displayTokenAnalysis(analysis) {
            const analysisHtml = `
                <div class="rtbcb-token-analysis">
                    <h4>Token Analysis Results</h4>
                    <div class="rtbcb-analysis-grid">
                        <div class="rtbcb-analysis-item">
                            <span class="label">Word Count:</span>
                            <span class="value">${analysis.wordCount}</span>
                        </div>
                        <div class="rtbcb-analysis-item">
                            <span class="label">Character Count:</span>
                            <span class="value">${analysis.charCount}</span>
                        </div>
                        <div class="rtbcb-analysis-item">
                            <span class="label">Estimated Tokens:</span>
                            <span class="value">${analysis.estimatedTokens}</span>
                        </div>
                        <div class="rtbcb-analysis-item">
                            <span class="label">Efficiency Score:</span>
                            <span class="value">${analysis.efficiency}/100</span>
                        </div>
                    </div>
                    <div class="rtbcb-optimization-suggestions">
                        <h5>Optimization Suggestions:</h5>
                        <ul>
                            ${analysis.suggestions.map(s => `<li>${s}</li>`).join('')}
                        </ul>
                    </div>
                </div>
            `;

            $('#token-analysis-display').html(analysisHtml);
            this.updateCostCalculator(analysis);
        },

        updateCostCalculator(analysis) {
            const models = ['mini', 'premium', 'advanced'];
            const costs = models.map(model => {
                const costPer1K = this.getModelCostPer1K(model);
                const estimatedCost = (analysis.estimatedTokens / 1000) * costPer1K;

                return `
                    <div class="rtbcb-cost-item">
                        <h6>${this.getModelDisplayName(model)}</h6>
                        <div class="rtbcb-cost-value">$${estimatedCost.toFixed(4)}</div>
                    </div>
                `;
            }).join('');

            $('#cost-calculator').html(costs);
        },

        displayOptimizationResult(data) {
            $('#prompt-to-optimize').val(data.optimized_prompt);

            const analysisHtml = `
                <div class="rtbcb-token-analysis">
                    <h4>Optimization Results</h4>
                    <div class="rtbcb-analysis-grid">
                        <div class="rtbcb-analysis-item">
                            <span class="label">Original Tokens:</span>
                            <span class="value">${data.original_analysis.estimated_tokens}</span>
                        </div>
                        <div class="rtbcb-analysis-item">
                            <span class="label">Optimized Tokens:</span>
                            <span class="value">${data.optimized_analysis.estimated_tokens}</span>
                        </div>
                        <div class="rtbcb-analysis-item">
                            <span class="label">Token Savings:</span>
                            <span class="value">${data.token_savings}</span>
                        </div>
                        <div class="rtbcb-analysis-item">
                            <span class="label">Efficiency Improvement:</span>
                            <span class="value">${data.efficiency_improvement}%</span>
                        </div>
                    </div>
                </div>
            `;

            $('#token-analysis-display').html(analysisHtml);

            const savingsHtml = Object.keys(data.cost_savings).map(model => {
                const saving = Number(data.cost_savings[model]).toFixed(6);
                return `
                    <div class="rtbcb-cost-item">
                        <h6>${this.getModelDisplayName(model)}</h6>
                        <div class="rtbcb-cost-value">-$${saving}</div>
                    </div>
                `;
            }).join('');

            $('#cost-calculator').html(savingsHtml);
        },

        optimizePrompt() {
            const prompt = $('#prompt-to-optimize').val().trim();

            if (!prompt) {
                this.showNotification('Please enter a prompt to optimize', 'error');
                return;
            }

            this.llmTestInProgress = true;
            this.setLoadingState(true, '#optimize-prompt', 'Optimizing...');

            $.ajax({
                url: rtbcbDashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rtbcb_optimize_prompt_tokens',
                    nonce: rtbcbDashboard.nonces.llm,
                    prompt: prompt
                },
                success: (res) => {
                    if (res.success) {
                        this.displayOptimizationResult(res.data);
                        this.showNotification('Prompt optimized successfully', 'success');
                    } else {
                        this.showNotification(res.data?.message || 'Optimization failed', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    this.showNotification('Optimization request failed: ' + error, 'error');
                },
                complete: () => {
                    this.llmTestInProgress = false;
                    this.setLoadingState(false, '#optimize-prompt', 'Optimize Prompt');
                }
            });
        },

        // Utility Functions
        getModelDisplayName(modelKey) {
            if (rtbcbDashboard.models && rtbcbDashboard.models[modelKey]) {
                const modelName = rtbcbDashboard.models[modelKey];
                return `${modelKey.charAt(0).toUpperCase() + modelKey.slice(1)} (${modelName})`;
            }
            return modelKey;
        },

        calculateModelPerformance(result) {
            let score = 50; // Base score

            // Response time scoring (lower is better)
            const responseTime = result.response_time || 0;
            if (responseTime < 2000) score += 20;
            else if (responseTime < 5000) score += 10;

            // Token efficiency scoring
            const tokensUsed = result.tokens_used || 0;
            const wordCount = result.word_count || 0;
            const tokenEfficiency = wordCount / Math.max(tokensUsed, 1);
            if (tokenEfficiency > 0.75) score += 15;
            else if (tokenEfficiency > 0.5) score += 10;

            // Content quality scoring
            const content = result.content || '';
            if (content.length > 100) score += 10;
            if (content.includes('ROI') || content.includes('business')) score += 5;

            return { score: Math.min(100, Math.max(0, score)) };
        },

        getPerformanceRating(score) {
            if (score >= 85) return { class: 'model-best', stars: 5, description: 'Excellent Performance' };
            if (score >= 70) return { class: 'model-good', stars: 4, description: 'Good Performance' };
            if (score >= 55) return { class: 'model-average', stars: 3, description: 'Average Performance' };
            return { class: 'model-poor', stars: 2, description: 'Needs Improvement' };
        },

        generateStarRating(starCount) {
            const fullStars = '★'.repeat(starCount);
            const emptyStars = '☆'.repeat(5 - starCount);
            return `<span class="rtbcb-star">${fullStars + emptyStars}</span>`;
        },

        calculateCost(tokens, modelKey) {
            const costPer1K = this.getModelCostPer1K(modelKey);
            return ((tokens / 1000) * costPer1K).toFixed(4);
        },

        getModelCostPer1K(modelKey) {
            const costs = {
                mini: 0.00015,     // GPT-4O Mini
                premium: 0.005,    // GPT-4O
                advanced: 0.015    // O1-Preview
            };
            return costs[modelKey] || 0.005;
        },

        calculateTokenEfficiency(prompt) {
            // Simple efficiency calculation based on word density and clarity
            const words = prompt.split(/\s+/);
            const uniqueWords = new Set(words.map(w => w.toLowerCase()));
            const redundancy = 1 - (uniqueWords.size / words.length);

            let efficiency = 70; // Base efficiency
            if (redundancy < 0.1) efficiency += 20;
            else if (redundancy < 0.2) efficiency += 10;

            // Penalize very long prompts
            if (words.length > 200) efficiency -= 10;

            return Math.min(100, Math.max(0, efficiency));
        },

        generateOptimizationSuggestions(prompt) {
            const suggestions = [];
            const words = prompt.split(/\s+/);

            if (words.length > 150) {
                suggestions.push('Consider shortening the prompt to reduce token usage');
            }

            if (prompt.includes('please') || prompt.includes('could you')) {
                suggestions.push('Remove politeness words to save tokens');
            }

            if (prompt.split('.').length > 5) {
                suggestions.push('Combine related sentences to improve efficiency');
            }

            const duplicateWords = this.findDuplicateWords(words);
            if (duplicateWords.length > 0) {
                suggestions.push(`Reduce repetitive words: ${duplicateWords.slice(0, 3).join(', ')}`);
            }

            if (suggestions.length === 0) {
                suggestions.push('Prompt appears well-optimized for token efficiency');
            }

            return suggestions;
        },

        findDuplicateWords(words) {
            const wordCounts = {};
            words.forEach(word => {
                const clean = word.toLowerCase().replace(/[^\w]/g, '');
                wordCounts[clean] = (wordCounts[clean] || 0) + 1;
            });

            return Object.keys(wordCounts)
                .filter(word => wordCounts[word] > 2 && word.length > 3)
                .sort((a, b) => wordCounts[b] - wordCounts[a]);
        },

        calculateReadabilityScore(text) {
            // Simplified readability calculation
            const words = text.split(/\s+/).length;
            const sentences = text.split(/[.!?]+/).length - 1;
            const avgWordsPerSentence = words / Math.max(sentences, 1);

            if (avgWordsPerSentence <= 15) return 'Easy';
            if (avgWordsPerSentence <= 20) return 'Moderate';
            return 'Complex';
        },

        getScoreClass(score) {
            if (score >= 85) return 'score-excellent';
            if (score >= 70) return 'score-good';
            if (score >= 55) return 'score-average';
            return 'score-poor';
        },

        // Load/Save Templates
        loadPromptTemplates() {
            // Load saved templates from localStorage or server
            const saved = localStorage.getItem('rtbcb_prompt_templates');
            if (saved) {
                try {
                    const templates = JSON.parse(saved);
                    Object.assign(this.promptTemplates, templates);
                } catch (e) {
                    console.warn('Failed to load saved prompt templates');
                }
            }
        },

        loadPromptTemplate() {
            // Show template selection dialog
            this.showNotification('Template loading feature coming soon!', 'info');
        },

        savePromptTemplate() {
            const systemPrompt = $('#llm-system-prompt').val().trim();
            const userPrompt = $('#llm-user-prompt').val().trim();

            if (!userPrompt) {
                this.showNotification('Please enter a prompt to save', 'error');
                return;
            }

            // Save template logic
            this.showNotification('Template saving feature coming soon!', 'info');
        },

        // Export Functions
        exportLLMResults() {
            if (!this.llmData) {
                this.showNotification('No results to export', 'warning');
                return;
            }

            const exportData = {
                test_type: 'model_comparison',
                timestamp: new Date().toISOString(),
                config: this.llmData.config,
                results: this.llmData.results,
                summary: {
                    models_tested: this.llmData.results.length,
                    best_performer: this.getBestPerformingModel(),
                    avg_response_time: this.calculateAverageResponseTime(),
                    total_tokens_used: this.calculateTotalTokensUsed()
                }
            };

            this.downloadJSON(exportData, `llm_comparison_${Date.now()}.json`);
            this.showNotification('LLM test results exported successfully', 'success');
        },

        getBestPerformingModel() {
            if (!this.llmData || !this.llmData.results.length) return null;

            let best = this.llmData.results[0];
            let bestScore = this.calculateModelPerformance(best.result).score;

            this.llmData.results.forEach(result => {
                const score = this.calculateModelPerformance(result.result).score;
                if (score > bestScore) {
                    best = result;
                    bestScore = score;
                }
            });

            return {
                model: this.getModelDisplayName(best.modelKey),
                score: bestScore
            };
        },

        calculateAverageResponseTime() {
            if (!this.llmData || !this.llmData.results.length) return 0;

            const total = this.llmData.results.reduce((sum, result) => {
                return sum + (result.result.response_time || 0);
            }, 0);

            return Math.round(total / this.llmData.results.length);
        },

        calculateTotalTokensUsed() {
            if (!this.llmData || !this.llmData.results.length) return 0;

            return this.llmData.results.reduce((sum, result) => {
                return sum + (result.result.tokens_used || 0);
            }, 0);
        },

        // RAG testing methods
        initRagModule() {
            this.validateRagQuery();
        },

        validateRagQuery() {
            const query = $('#rtbcb-rag-query').val().trim();
            const disabled = query.length === 0 || this.ragRequest !== null;
            $('[data-action="run-rag-test"]').prop('disabled', disabled);
        },

        runRagQuery() {
            if (this.ragRequest) return;

            const query = $('#rtbcb-rag-query').val().trim();
            const topK = parseInt($('#rtbcb-rag-top-k').val(), 10) || 3;
            const type = $('#rtbcb-rag-type').val();
            if (!query) {
                this.showNotification('Please enter a query', 'error');
                return;
            }

            this.ragRequest = $.ajax({
                url: rtbcbDashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rtbcb_test_rag_query',
                    nonce: rtbcbDashboard.nonces.dashboard,
                    query,
                    top_k: topK,
                    type
                }
            });

            $('#rtbcb-rag-progress').text(rtbcbDashboard.strings.retrieving).show();
            $('#rtbcb-rag-cancel').show();
            this.validateRagQuery();

            this.ragRequest.done((res) => {
                if (res.success) {
                    this.displayRagResults(res.data);
                    this.showNotification('Retrieval complete', 'success');
                } else {
                    this.showNotification(res.data?.message || rtbcbDashboard.strings.error, 'error');
                }
            }).fail(() => {
                this.showNotification(rtbcbDashboard.strings.error, 'error');
            }).always(() => {
                this.ragRequest = null;
                $('#rtbcb-rag-progress').hide();
                $('#rtbcb-rag-cancel').hide();
                this.validateRagQuery();
            });
        },

        cancelRagQuery() {
            if (this.ragRequest) {
                this.ragRequest.abort();
                this.ragRequest = null;
                $('#rtbcb-rag-progress').hide();
                $('#rtbcb-rag-cancel').hide();
                this.validateRagQuery();
                this.showNotification('Retrieval cancelled', 'info');
            }
        },

        displayRagResults(data) {
            this.ragResults = data.results || [];
            this.ragContextText = this.ragResults.map(r => {
                const md = r.metadata || {};
                return md.description || md.content || md.name || '';
            }).join("\n");

            const metrics = data.metrics || {};
            $('#rtbcb-rag-metrics').text(
                `Time: ${metrics.retrieval_time || 0} ms | Results: ${metrics.result_count || 0} | Avg score: ${(metrics.average_score || 0).toFixed(3)}`
            );

            const tbody = $('#rtbcb-rag-results-table tbody').empty();
            this.ragResults.forEach(row => {
                const score = parseFloat(row.score || 0);
                let title = row.metadata?.name || row.metadata?.title || row.metadata?.description || row.metadata?.content || '';
                const cls = score >= 0.8 ? 'status-good' : (score >= 0.5 ? 'status-warning' : 'status-error');
                const tr = $('<tr>').addClass(cls)
                    .append(`<td>${this.escapeHtml(row.type)}</td>`)
                    .append(`<td>${this.escapeHtml(row.ref_id)}</td>`)
                    .append(`<td>${this.escapeHtml(title)}</td>`)
                    .append(`<td>${score.toFixed(3)}</td>`);
                tbody.append(tr);
            });

            if (this.ragResults.length) {
                $('#rtbcb-rag-results').show();
                $('#rtbcb-copy-rag-context, #rtbcb-export-rag-results').prop('disabled', false);
            } else {
                $('#rtbcb-rag-results').hide();
                $('#rtbcb-copy-rag-context, #rtbcb-export-rag-results').prop('disabled', true);
                this.showNotification(rtbcbDashboard.strings.noResults, 'warning');
            }

            const debug = data.debug || {};
            $('#rtbcb-rag-debug pre').text(JSON.stringify(debug, null, 2));

            if (data.index_info) {
                if (data.index_info.last_indexed) {
                    $('#rtbcb-rag-last-indexed').text(
                        rtbcbDashboard.strings.lastIndexed.replace('%s', data.index_info.last_indexed)
                    );
                }
                if (typeof data.index_info.index_size !== 'undefined') {
                    $('#rtbcb-rag-index-size').text(
                        rtbcbDashboard.strings.entries.replace('%d', data.index_info.index_size)
                    );
                }
            }
        },

        copyRagContext() {
            if (!this.ragContextText) {
                this.showNotification('No results to copy', 'warning');
                return;
            }
            navigator.clipboard.writeText(this.ragContextText).then(() => {
                this.showNotification('Context copied', 'success');
            }).catch(() => {
                this.showNotification('Failed to copy', 'error');
            });
        },

        exportRagResults() {
            if (!this.ragResults.length) {
                this.showNotification('No results to export', 'warning');
                return;
            }
            const blob = new Blob([JSON.stringify(this.ragResults, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'rag-results.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            this.showNotification('Results exported', 'success');
        },

        rebuildRagIndex() {
            const btn = $('#rtbcb-rag-rebuild').prop('disabled', true);
            $('#rtbcb-rag-index-notice').text(rtbcbDashboard.strings.retrieving);
            $.post(rtbcbDashboard.ajaxurl, {
                action: 'rtbcb_rag_rebuild_index',
                nonce: rtbcbDashboard.nonces.dashboard
            }).done((res) => {
                if (res.success) {
                    $('#rtbcb-rag-index-notice').text(rtbcbDashboard.strings.indexRebuilt);
                    if (res.data.last_indexed) {
                        $('#rtbcb-rag-last-indexed').text(
                            rtbcbDashboard.strings.lastIndexed.replace('%s', res.data.last_indexed)
                        );
                    }
                    if (typeof res.data.index_size !== 'undefined') {
                        $('#rtbcb-rag-index-size').text(
                            rtbcbDashboard.strings.entries.replace('%d', res.data.index_size)
                        );
                    }
                    $('#rtbcb-rag-index-status').removeClass('status-warning').addClass('status-good');
                } else {
                    $('#rtbcb-rag-index-notice').text(res.data?.message || rtbcbDashboard.strings.rebuildFailed);
                }
            }).fail(() => {
                $('#rtbcb-rag-index-notice').text(rtbcbDashboard.strings.rebuildFailed);
            }).always(() => {
                btn.prop('disabled', false);
            });
        },

        // API Health Methods
        initApiHealth() {
            this.apiResults = rtbcbDashboard.apiHealth?.lastResults?.results || {};
            this.updateApiSummary();
        },

        runAllApiTests() {
            $('[data-action="api-health-ping"]').prop('disabled', true);
            $('#rtbcb-api-health-notice').text(rtbcbDashboard.strings.running);

            $.post(rtbcbDashboard.ajaxurl, {
                action: 'rtbcb_run_api_health_tests',
                nonce: rtbcbDashboard.nonces.apiHealth
            }).done((response) => {
                if (response.success) {
                    const data = response.data;
                    this.apiResults = data.results;
                    Object.keys(data.results).forEach(key => {
                        this.updateApiRow(key, data.results[key], data.timestamp);
                    });
                    this.updateApiSummary();
                } else {
                    $('#rtbcb-api-health-notice').text(rtbcbDashboard.strings.error);
                }
            }).fail(() => {
                $('#rtbcb-api-health-notice').text(rtbcbDashboard.strings.error);
            }).always(() => {
                $('[data-action="api-health-ping"]').prop('disabled', false);
            });
        },

        runSingleApiTest(component) {
            const button = $(`.rtbcb-retest[data-component="${component}"]`).prop('disabled', true);
            $.post(rtbcbDashboard.ajaxurl, {
                action: 'rtbcb_run_single_api_test',
                nonce: rtbcbDashboard.nonces.apiHealth,
                component
            }).done((response) => {
                if (response.success) {
                    const res = response.data.result;
                    this.apiResults[component] = res;
                    this.updateApiRow(component, res, response.data.timestamp);
                    this.updateApiSummary();
                } else {
                    $('#rtbcb-api-health-notice').text(rtbcbDashboard.strings.error);
                }
            }).fail(() => {
                $('#rtbcb-api-health-notice').text(rtbcbDashboard.strings.error);
            }).always(() => {
                button.prop('disabled', false);
            });
        },

        updateApiRow(component, result, timestamp) {
            const row = $(`#rtbcb-api-${component}`);
            const indicator = row.find('.rtbcb-status-indicator');
            indicator.removeClass('status-good status-error');
            indicator.find('.dashicons').removeClass('dashicons-yes-alt dashicons-warning dashicons-minus');

            if (result.passed) {
                indicator.addClass('status-good');
                indicator.find('.dashicons').addClass('dashicons-yes-alt');
            } else {
                indicator.addClass('status-error');
                indicator.find('.dashicons').addClass('dashicons-warning');
            }

            if (timestamp) {
                result.last_tested = timestamp;
            }
            row.find('.rtbcb-last-tested').text(result.last_tested || '');
            row.find('.rtbcb-response-time').text(result.response_time ? `${result.response_time} ms` : '');
            let msg = result.message || '';
            if (!result.passed && /API key/i.test(msg) && rtbcbDashboard.urls?.settings) {
                msg += ` <a href="${rtbcbDashboard.urls.settings}">${rtbcbDashboard.strings.settings}</a>`;
            }
            row.find('.rtbcb-message').html(msg);
            $(`#rtbcb-details-${component} pre`).text(JSON.stringify(result.details || {}, null, 2));
        },

        toggleApiDetails(component) {
            $(`#rtbcb-details-${component}`).toggle();
        },

        updateApiSummary() {
            const results = this.apiResults || {};
            const failures = Object.values(results).filter(r => !r.passed).length;
            let message;
            if (Object.keys(results).length === 0) {
                message = rtbcbDashboard.strings.notTested;
            } else if (failures === 0) {
                message = rtbcbDashboard.strings.allOperational;
            } else {
                message = rtbcbDashboard.strings.errorsDetected.replace('%d', failures);
            }
            $('#rtbcb-api-health-notice').text(message);
        },

        // Run data health checks
        runDataHealthChecks() {
            const button = $('#rtbcb-run-data-health').prop('disabled', true);
            $('#rtbcb-data-health-results').html(`<tr><td colspan="3">${rtbcbDashboard.strings.running}</td></tr>`);

            $.post(rtbcbDashboard.ajaxurl, {
                action: 'rtbcb_run_data_health_checks',
                nonce: rtbcbDashboard.nonces.dataHealth
            }).done((response) => {
                if (response.success) {
                    const rows = Object.values(response.data).map(check => {
                        const icon = check.passed ? 'dashicons-yes-alt' : 'dashicons-warning';
                        const statusClass = check.passed ? 'status-good' : 'status-error';
                        return `<tr>
                            <td><span class="rtbcb-status-indicator ${statusClass}"><span class="dashicons ${icon}"></span></span></td>
                            <td>${this.escapeHtml(check.label)}</td>
                            <td>${this.escapeHtml(check.message || '')}</td>
                        </tr>`;
                    }).join('');
                    $('#rtbcb-data-health-results').html(rows);
                } else {
                    $('#rtbcb-data-health-results').html(`<tr><td colspan="3">${rtbcbDashboard.strings.error}</td></tr>`);
                }
            }).fail(() => {
                $('#rtbcb-data-health-results').html(`<tr><td colspan="3">${rtbcbDashboard.strings.error}</td></tr>`);
            }).always(() => {
                button.prop('disabled', false);
            });
        },

        // Generate report preview
        generatePreviewReport() {
            const button = $('#rtbcb-generate-preview-report').prop('disabled', true);

            $.post(rtbcbDashboard.ajaxurl, {
                action: 'rtbcb_generate_preview_report',
                nonce: rtbcbDashboard.nonces.reportPreview
            }).done((response) => {
                if (response.success) {
                    $('#rtbcb-report-preview-frame').attr('srcdoc', response.data.html || '');
                } else {
                    this.showNotification(response.data?.message || rtbcbDashboard.strings.error, 'error');
                }
            }).fail(() => {
                this.showNotification(rtbcbDashboard.strings.error, 'error');
            }).always(() => {
                button.prop('disabled', false);
            });
        },

        // Save dashboard settings
        saveDashboardSettings(e) {
            e.preventDefault();
            const $form = $('#rtbcb-dashboard-settings-form');
            const data = {
                action: 'rtbcb_save_dashboard_settings',
                nonce: $form.find('[name="nonce"]').val(),
                rtbcb_openai_api_key: $('#rtbcb_openai_api_key').val(),
                rtbcb_mini_model: $('#rtbcb_mini_model').val(),
                rtbcb_premium_model: $('#rtbcb_premium_model').val(),
                rtbcb_advanced_model: $('#rtbcb_advanced_model').val(),
                rtbcb_embedding_model: $('#rtbcb_embedding_model').val()
            };
            const $button = $form.find('button[type="submit"]').prop('disabled', true);

            $.post(rtbcbDashboard.ajaxurl, data).done((response) => {
                if (response.success) {
                    this.showNotification(rtbcbDashboard.strings.settingsSaved, 'success');
                } else {
                    this.showNotification(response.data?.message || rtbcbDashboard.strings.error, 'error');
                }
            }).fail(() => {
                this.showNotification(rtbcbDashboard.strings.error, 'error');
            }).always(() => {
                $button.prop('disabled', false);
            });
        },

        // Validation
        validateLLMInputs() {
            const userPrompt = $('#llm-user-prompt').val().trim();
            const selectedModels = $('input[name="llm-models[]"]:checked').length;

            const isValid = userPrompt.length > 0 && selectedModels > 0;
            $('[data-action="run-llm-test"]').prop('disabled', !isValid || this.llmTestInProgress);
        },

        // Setup Charts
        setupLLMCharts() {
            // Chart.js setup if needed
        },

        // Utility function for HTML escaping
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });

    // Initialize when DOM is ready
    $(document).ready(() => {
        Dashboard.init();
        if (typeof Dashboard.initLLMIntegration === 'function') {
            Dashboard.initLLMIntegration();
        }
    });

    // Expose Dashboard object for debugging
    window.RTBCBDashboard = Dashboard;

    // Add this debug helper
    window.DashboardDiag = {
        assertNonce: function(action) {
            const nonces = rtbcbDashboard?.nonces || {};
            console.log(`[DIAG] Nonce check for ${action}:`, nonces[action] ? 'FOUND' : 'MISSING');
            return !!nonces[action];
        },

        assertTabVisibility: function() {
            const activeTab = $('.rtbcb-test-section.active');
            console.log('[DIAG] Active tab:', activeTab.attr('id'), 'Visible:', activeTab.is(':visible'));
            return activeTab.length > 0 && activeTab.is(':visible');
        },

        countHandlers: function(selector) {
            const events = $._data($(selector)[0], 'events') || {};
            const count = Object.keys(events).length;
            console.log(`[DIAG] Event handlers on ${selector}:`, count, events);
            return count;
        },

        checkOverlays: function() {
            const overlays = $('.rtbcb-loading, .rtbcb-overlay, .rtbcb-modal').filter(':visible');
            console.log('[DIAG] Visible overlays:', overlays.length);
            overlays.each(function() {
                const zIndex = $(this).css('z-index');
                const pointerEvents = $(this).css('pointer-events');
                console.log(`[DIAG] Overlay ${this.className}: z-index=${zIndex}, pointer-events=${pointerEvents}`);
            });
            return overlays.length;
        }
    };

    // Bind diagnostic to button clicks
    $(document).on('click', '[data-action]', function(e) {
        const action = $(this).data('action');
        console.log(`[DIAG] Button click: ${action}, disabled=${$(this).prop('disabled')}, preventDefault called`);
        window.DashboardDiag.assertNonce(action.replace('run-', '').replace('-test', ''));
        window.DashboardDiag.checkOverlays();
    });

})(jQuery);

