/**
 * Unified Test Dashboard JavaScript
 * Handles all dashboard functionality including company overview testing,
 * progress tracking, debug information, and result display.
 */
(function($) {
    'use strict';

    if ( typeof rtbcbDashboard === 'undefined' ) {
        console.error( 'rtbcbDashboard is not defined' );
        return;
    }

    console.log( 'Test dashboard script loaded' );
    console.log( 'AJAX URL:', rtbcbDashboard.ajaxurl );
    console.log( 'Nonces:', rtbcbDashboard.nonces );

    const debounce = (func, delay) => {
        let timeoutId;
        return (...args) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    };

    const circuitBreaker = {
        failures: 0,
        threshold: 5, // Increased from 3
        resetTime: 60000, // Reduced from 300000 (1 minute)

        canExecute() {
            return this.failures < this.threshold;
        },

        recordFailure() {
            this.failures++;
            console.warn(`[Circuit Breaker] Failure recorded. Count: ${this.failures}/${this.threshold}`);
            if (this.failures >= this.threshold) {
                console.error(`[Circuit Breaker] Threshold reached. Resetting in ${this.resetTime}ms`);
                setTimeout(() => this.reset(), this.resetTime);
            }
        },

        recordSuccess() {
            console.log('[Circuit Breaker] Success recorded. Resetting failure count.');
            this.failures = 0;
        },

        reset() {
            console.log('[Circuit Breaker] Reset');
            this.failures = 0;
        }
    };

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
        llmTestResults: null,
        charts: {},
        roiPresets: {
            'small-company': {
                'roi-company-size': 'small',
                'roi-annual-revenue': 20000000,
                'roi-industry': 'technology',
                'roi-treasury-staff': 2,
                'roi-avg-salary': 65000,
                'roi-hours-reconciliation': 1,
                'roi-hours-reporting': 1,
                'roi-hours-analysis': 1,
                'roi-num-banks': 2,
                'roi-monthly-bank-fees': 3000,
                'roi-wire-transfer-volume': 40,
                'roi-avg-wire-fee': 20,
                'roi-error-frequency': 1,
                'roi-avg-error-cost': 1000,
                'roi-compliance-hours': 10,
                'roi-system-integration': 'manual'
            },
            'medium-company': {
                'roi-company-size': 'medium',
                'roi-annual-revenue': 50000000,
                'roi-industry': 'manufacturing',
                'roi-treasury-staff': 5,
                'roi-avg-salary': 85000,
                'roi-hours-reconciliation': 4,
                'roi-hours-reporting': 2,
                'roi-hours-analysis': 3,
                'roi-num-banks': 8,
                'roi-monthly-bank-fees': 15000,
                'roi-wire-transfer-volume': 150,
                'roi-avg-wire-fee': 25,
                'roi-error-frequency': 3,
                'roi-avg-error-cost': 2500,
                'roi-compliance-hours': 40,
                'roi-system-integration': 'partial'
            },
            'large-company': {
                'roi-company-size': 'large',
                'roi-annual-revenue': 300000000,
                'roi-industry': 'financial-services',
                'roi-treasury-staff': 20,
                'roi-avg-salary': 110000,
                'roi-hours-reconciliation': 6,
                'roi-hours-reporting': 4,
                'roi-hours-analysis': 5,
                'roi-num-banks': 20,
                'roi-monthly-bank-fees': 60000,
                'roi-wire-transfer-volume': 1000,
                'roi-avg-wire-fee': 30,
                'roi-error-frequency': 10,
                'roi-avg-error-cost': 5000,
                'roi-compliance-hours': 200,
                'roi-system-integration': 'integrated'
            }
        },
        lastRoiResults: null,

        // Initialize dashboard
        init: function() {
            this.bindEvents();
            this.initializeTabs();
            this.checkSystemStatus();
            this.initApiHealth();
            this.initRagModule();
            this.initLLMModule();
            this.setupCharts();

            $('#company-name-input').on('input', debounce(this.validateInput.bind(this), 300));
            this.validateInput();

            // Store default button text for state management
            $('[data-action]').each(function() {
                $(this).data('default-text', $(this).html());
            });

            // Re-init after tab switches
            $(document).on('rtbcb:tab-switched', this.reinitializeCurrentTab.bind(this));

            setTimeout(() => {
                $('*').each(function() {
                    const zIndex = parseInt($(this).css('z-index'));
                    if (zIndex > 1000 && $(this).is(':visible')) {
                        console.warn('[DIAG] High z-index overlay:', this, zIndex);
                    }
                });
            }, 1000);
        },

        reinitializeCurrentTab: function() {
            // Destroy charts in hidden tabs to prevent memory leaks
            Object.keys(this.charts).forEach(chartId => {
                const chartElement = document.getElementById(chartId);
                if (chartElement && !$(chartElement).is(':visible')) {
                    this.destroyChart(chartId);
                }
            });

            // Rebind any dynamic content in the active tab
            const activeTab = this.currentTab;

            switch (activeTab) {
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
            this.bindLLMEvents();
            this.validateLLMInputs();
        },

        setupCharts: function() {
            this.loadChartJs(() => {
                if (typeof this.setupLLMCharts === 'function') {
                    this.setupLLMCharts();
                }
            });
        },

        loadChartJs: function(callback) {
            if (typeof Chart !== 'undefined') {
                callback();
            }
        },

        // Bind all event handlers using delegated pattern
        bindEvents() {
            // Use delegated handlers for dynamic content
            $(document).off('.rtbcb').on('click.rtbcb', '[data-action="run-company-overview"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.generateCompanyOverview();
                } catch (err) {
                    console.error('Error generating company overview:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="clear-results"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.clearResults();
                } catch (err) {
                    console.error('Error clearing results:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="export-results"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.exportResults();
                } catch (err) {
                    console.error('Error exporting results:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="copy-results"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.copyResults();
                } catch (err) {
                    console.error('Error copying results:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="regenerate-results"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.regenerateResults();
                } catch (err) {
                    console.error('Error regenerating results:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="toggle-debug"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.toggleDebugPanel();
                } catch (err) {
                    console.error('Error toggling debug panel:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="retry-request"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.retryRequest();
                } catch (err) {
                    console.error('Error retrying request:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="run-llm-test"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.runModelComparison();
                } catch (err) {
                    console.error('Error running LLM test:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="run-rag-test"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.runRagTest();
                } catch (err) {
                    console.error('Error running RAG test:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="rebuild-rag-index"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.rebuildRagIndex();
                } catch (err) {
                    console.error('Error rebuilding RAG index:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="cancel-rag-test"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.cancelRagQuery();
                } catch (err) {
                    console.error('Error cancelling RAG query:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="copy-rag-context"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.copyRagContext();
                } catch (err) {
                    console.error('Error copying RAG context:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="export-rag-results"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.exportRagResults();
                } catch (err) {
                    console.error('Error exporting RAG results:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="calculate-roi"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.calculateRoiTest();
                } catch (err) {
                    console.error('Error calculating ROI:', err);
                }
            });

            $(document).on('click.rtbcb', '.rtbcb-scenario-tab', function(e) {
                e.preventDefault();
                const scenario = $(this).data('scenario');
                $('.rtbcb-scenario-tab').removeClass('active');
                $(this).addClass('active');
                try {
                    Dashboard.loadRoiScenario(scenario);
                } catch (err) {
                    console.error('Error loading ROI scenario:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="export-roi-results"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.exportRoiResults();
                } catch (err) {
                    console.error('Error exporting ROI results:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="api-health-ping"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.runAllApiTests();
                } catch (err) {
                    console.error('Error running API health tests:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="api-health-retest"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.runSingleApiTest($(e.currentTarget).data('component'));
                } catch (err) {
                    console.error('Error running single API test:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="run-data-health"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.runDataHealthChecks();
                } catch (err) {
                    console.error('Error running data health checks:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="generate-preview-report"]', function(e) {
                e.preventDefault();
                try {
                    Dashboard.generatePreviewReport();
                } catch (err) {
                    console.error('Error generating preview report:', err);
                }
            });

            $(document).on('click.rtbcb', '[data-action="toggle-api-key"]', function() {
                const $input = $('#rtbcb_openai_api_key');
                const input = $input[0];
                const currentValue = $input.val();
                const isPassword = input.type === 'password';

                // Toggle using DOM property to ensure value persists
                input.type = isPassword ? 'text' : 'password';
                $input.val(currentValue);

                $(this).text(isPassword ? rtbcbDashboard.strings.hide : rtbcbDashboard.strings.show);
            });

            $(document).on('submit.rtbcb', '#rtbcb-dashboard-settings-form', this.saveDashboardSettings.bind(this));

            // Tab navigation
            $('.rtbcb-test-tabs .nav-tab').on('click.rtbcb', this.handleTabClick.bind(this));
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
            const defaultText = $button.data('default-text') || $button.text();

            $button.removeClass('rtbcb-loading rtbcb-success rtbcb-error');

            switch (state) {
                case 'loading':
                    $button.prop('disabled', true)
                           .attr('aria-busy', 'true')
                           .addClass('rtbcb-loading')
                           .html(`<span class="dashicons dashicons-update rtbcb-spin"></span> ${text || 'Loading...'}`);
                    break;

                case 'success':
                    $button.prop('disabled', false)
                           .attr('aria-busy', 'false')
                           .addClass('rtbcb-success')
                           .html(`<span class="dashicons dashicons-yes-alt"></span> ${text || 'Complete'}`);
                    setTimeout(() => $button.removeClass('rtbcb-success').html(defaultText), 3000);
                    break;

                case 'error':
                    $button.prop('disabled', false)
                           .attr('aria-busy', 'false')
                           .addClass('rtbcb-error')
                           .html(`<span class="dashicons dashicons-warning"></span> ${text || 'Error'}`);
                    setTimeout(() => $button.removeClass('rtbcb-error').html(defaultText), 5000);
                    break;

                case 'ready':
                default:
                    $button.prop('disabled', false)
                           .attr('aria-busy', 'false')
                           .html(text || defaultText);
                    break;
            }
        },

        // Promise-based request wrapper with retry logic
        request: function(action, data = {}, options = {}) {
            const defaults = {
                retries: 3,
                backoffMs: 1000,
                timeout: 60000,
                validateResponse: true
            };

            const config = Object.assign({}, defaults, options);

            return new Promise((resolve, reject) => {
                const attemptRequest = (attemptNum) => {
                    const nonceKey = this.getNonceKeyForAction(action);
                    if (!rtbcbDashboard.nonces[nonceKey]) {
                        reject(new Error(`Missing nonce for action: ${action}`));
                        return;
                    }

                    const requestData = Object.assign({
                        action: `rtbcb_${action}`,
                        nonce: rtbcbDashboard.nonces[nonceKey]
                    }, data);

                    $.ajax({
                        url: rtbcbDashboard.ajaxurl,
                        type: 'POST', 
                        data: requestData,
                        timeout: config.timeout,

                        success: (response) => {
                            if (config.validateResponse && (!response || typeof response.success === 'undefined')) {
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
                            const isRateLimit = xhr.status === 429;
                            const shouldRetry = attemptNum < config.retries;

                            if (shouldRetry && (isRateLimit || status === 'timeout')) {
                                const delay = isRateLimit ? 
                                    config.backoffMs * Math.pow(2, attemptNum - 1) : 
                                    config.backoffMs;
                                setTimeout(() => attemptRequest(attemptNum + 1), delay);
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
            if (this.charts[chartId]) {
                this.charts[chartId].destroy();
                delete this.charts[chartId];
            }
        },

        createChart: function(chartId, config) {
            this.destroyChart(chartId);

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

            this.debugLog('Generate Company Overview button clicked', { companyName, model, showDebug });

            if (!companyName) {
                this.debugLog('Company overview aborted: missing company name', null, 'error');
                this.showNotification('Please enter a company name', 'error');
                return;
            }

            this.startGeneration(companyName, model, showDebug);
        },

        runModelComparison: function() {
            if (this.isGenerating) return;

            const prompt = $('#llm-test-prompt').val().trim();
            const selectedModels = $('input[name="test-models[]"]:checked').map(function() {
                return $(this).val();
            }).get();
            const maxTokens = parseInt($('#llm-max-tokens').val());
            const temperature = parseFloat($('#llm-temperature').val());

            if (!prompt || selectedModels.length === 0) {
                this.showNotification('Please enter a prompt and select at least one model', 'error');
                return;
            }

            this.isGenerating = true;
            this.setButtonState('[data-action="run-llm-test"]', 'loading', 'Testing Models...');
            this.hideContainers(['llm-test-results']);

            const requestData = {
                modelIds: selectedModels,
                promptA: prompt,
                maxTokens: maxTokens,
                temperature: temperature,
                runMode: 'matrix'
            };

            this.request('run_llm_test', requestData)
                .then((data) => {
                    this.displayLLMResults(data);
                    this.setButtonState('[data-action="run-llm-test"]', 'success', 'Tests Complete');
                    this.showNotification(`LLM tests completed for ${selectedModels.length} models`, 'success');
                })
                .catch((error) => {
                    this.setButtonState('[data-action="run-llm-test"]', 'error', 'Test Failed');
                    this.showNotification('LLM test failed: ' + error.message, 'error');
                })
                .finally(() => {
                    this.isGenerating = false;
                    setTimeout(() => {
                        this.setButtonState('[data-action="run-llm-test"]', 'ready');
                    }, 3000);
                });
        },

        // LLM Integration Testing Methods
        bindLLMEvents: function() {
            // Temperature slider update
            $('#llm-temperature').on('input', debounce(function() {
                $('#llm-temperature-value').text($(this).val());
            }, 100));

            // Model selection validation
            $('input[name="test-models[]"]').on('change', this.validateLLMInputs.bind(this));

            // Prompt validation
            $('#llm-test-prompt').on('input', debounce(this.validateLLMInputs.bind(this), 300));
        },

        validateLLMInputs: function() {
            const prompt = $('#llm-test-prompt').val().trim();
            const selectedModels = $('input[name="test-models[]"]:checked').length;

            const isValid = prompt.length > 0 && selectedModels > 0;
            $('[data-action="run-llm-test"]').prop('disabled', !isValid);
        },

        displayLLMResults: function(data) {
            const render = () => {
                const results = data.results || {};
                const metadata = data.metadata || {};

                // Update results metadata
                $('#llm-results-meta').html(`
                    <div class="rtbcb-meta-item"><strong>Models Tested:</strong> ${metadata.modelsCount || 0}</div>
                    <div class="rtbcb-meta-item"><strong>Total Time:</strong> ${metadata.totalTime || 0}s</div>
                    <div class="rtbcb-meta-item"><strong>Timestamp:</strong> ${metadata.timestamp || ''}</div>
                `);

                // Create performance summary
                this.createLLMPerformanceSummary(results);

                // Populate comparison table
                this.populateLLMComparisonTable(results);

                // Create performance chart
                this.createLLMPerformanceChart(results);

                // Store results for export
                this.llmTestResults = data;
                $('[data-action="export-results"][data-export-type="llm"]').prop('disabled', false);

                // Show results container
                $('#llm-test-results').show().addClass('rtbcb-fade-in');
            };

            if ('requestIdleCallback' in window) {
                requestIdleCallback(render);
            } else {
                setTimeout(render, 0);
            }
        },

        createLLMPerformanceSummary: function(results) {
            const successfulResults = Object.values(results).filter(r => r.success);
            const failedCount = Object.values(results).length - successfulResults.length;

            if (successfulResults.length === 0) {
                $('#llm-performance-summary').html('<div class="rtbcb-error">All model tests failed</div>');
                return;
            }

            const avgResponseTime = successfulResults.reduce((sum, r) => sum + r.response_time, 0) / successfulResults.length;
            const totalTokens = successfulResults.reduce((sum, r) => sum + r.tokens_used, 0);
            const totalCost = successfulResults.reduce((sum, r) => sum + r.cost_estimate, 0);
            const avgQuality = successfulResults.reduce((sum, r) => sum + r.quality_score, 0) / successfulResults.length;

            const summaryHtml = `
                <div class="rtbcb-summary-cards">
                    <div class="rtbcb-summary-card">
                        <h4>Average Response Time</h4>
                        <div class="rtbcb-metric-value">${Math.round(avgResponseTime)}ms</div>
                    </div>
                    <div class="rtbcb-summary-card">
                        <h4>Total Tokens Used</h4>
                        <div class="rtbcb-metric-value">${totalTokens.toLocaleString()}</div>
                    </div>
                    <div class="rtbcb-summary-card">
                        <h4>Estimated Cost</h4>
                        <div class="rtbcb-metric-value">$${totalCost.toFixed(4)}</div>
                    </div>
                    <div class="rtbcb-summary-card">
                        <h4>Average Quality</h4>
                        <div class="rtbcb-metric-value">${Math.round(avgQuality)}/100</div>
                    </div>
                </div>
            `;

            $('#llm-performance-summary').html(summaryHtml);
        },

        populateLLMComparisonTable: function(results) {
            const tbody = $('#llm-comparison-tbody').empty();
            const fragment = document.createDocumentFragment();

            Object.values(results).forEach(result => {
                const statusClass = result.success ? 'success' : 'error';
                const responsePreview = result.success ?
                    (result.content.substring(0, 100) + (result.content.length > 100 ? '...' : '')) :
                    result.error;

                const row = document.createElement('tr');
                row.className = `rtbcb-result-row rtbcb-${statusClass}`;
                row.innerHTML = `
                        <td><strong>${this.escapeHtml(result.model_name)}</strong><br><small>${result.model_key}</small></td>
                        <td>${result.response_time || '--'}ms</td>
                        <td>${result.tokens_used || '--'}</td>
                        <td>$${result.success ? result.cost_estimate.toFixed(6) : '--'}</td>
                        <td>${result.success ? result.quality_score + '/100' : '--'}</td>
                        <td class="rtbcb-response-preview">${this.escapeHtml(responsePreview)}</td>
                `;
                fragment.appendChild(row);
            });

            tbody.append(fragment);
        },

        createLLMPerformanceChart: function(results) {
            const successfulResults = Object.values(results).filter(r => r.success);

            if (successfulResults.length === 0) return;

            const labels = successfulResults.map(r => r.model_key);
            const responseTimes = successfulResults.map(r => r.response_time);
            const qualityScores = successfulResults.map(r => r.quality_score);
            const costs = successfulResults.map(r => r.cost_estimate * 1000); // Convert to per-1K for scale

            const chartConfig = {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Response Time (ms)',
                        data: responseTimes,
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        yAxisID: 'y'
                    }, {
                        label: 'Quality Score',
                        data: qualityScores,
                        type: 'line',
                        backgroundColor: 'rgba(255, 99, 132, 0.8)',
                        borderColor: 'rgba(255, 99, 132, 1)',
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
                            title: { display: true, text: 'Response Time (ms)' }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: { display: true, text: 'Quality Score' },
                            min: 0,
                            max: 100,
                            grid: { drawOnChartArea: false }
                        }
                    },
                    plugins: {
                        title: { display: true, text: 'LLM Performance Comparison' },
                        legend: { position: 'top' }
                    }
                }
            };

            this.createChart('llm-performance-chart', chartConfig);
        },

        // Start the generation process
        startGeneration(companyName, model, showDebug) {
            if (!circuitBreaker.canExecute()) {
                this.showNotification(rtbcbDashboard.strings.error, 'error');
                return;
            }

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

            this.debugLog('Company overview request payload', requestData);

            // Make AJAX request
            this.currentRequest = $.ajax({
                url: rtbcbDashboard.ajaxurl,
                type: 'POST',
                data: requestData,
                timeout: 60000,

                success: (response, textStatus, xhr) => {
                    circuitBreaker.recordSuccess();
                    const logData = { status: xhr.status, response };
                    if (response.success) {
                        this.debugLog('Company overview response success', logData);
                    } else {
                        this.debugLog('Company overview response failure', logData, 'error');
                    }
                    this.handleGenerationSuccess(response);
                },

                error: (xhr, status, error) => {
                    circuitBreaker.recordFailure();
                    this.debugLog('Company overview request failed', {
                        status: xhr ? xhr.status : 0,
                        statusText: status,
                        error: error,
                        response: xhr ? xhr.responseText : null
                    }, 'error');
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
            } else if (status === 'parsererror') {
                errorMessage = 'Received invalid response from server.';
                if (xhr && xhr.responseText) {
                    console.error('Server response:', xhr.responseText);
                }
            } else if (xhr && xhr.responseJSON) {
                errorMessage = xhr.responseJSON.data?.message || errorMessage;
                debugInfo = xhr.responseJSON.data?.debug || {};

                const detail = xhr.responseJSON.data?.detail;
                if (detail) {
                    debugInfo.detail = detail;
                }
            } else if (error) {
                errorMessage = error;
            }

            this.showError(errorMessage, debugInfo);
            this.showNotification(errorMessage, 'error');
            this.showDebugPanel();
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
            $('[data-action="export-results"], [data-action="copy-results"], [data-action="regenerate-results"]').prop('disabled', false);

            // Hide progress
            this.hideProgressContainer();
        },

        // Show error information
        showError(message, debugInfo = {}) {
            const errorContainer = $('#error-container');
            const errorContent = $('#error-content');
            const errorDebug = $('#error-debug');

            const safeMessage = $('<div/>').text(message).html();
            errorContent.html(`<strong>Error:</strong> ${safeMessage}`);

            if (Object.keys(debugInfo).length > 0) {
                errorDebug.text(JSON.stringify(debugInfo, null, 2)).show();
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
        setLoadingState(loading, buttonSelector = '[data-action="run-company-overview"]', text = loading ? rtbcbDashboard.strings.generating : rtbcbDashboard.strings.generateOverview) {
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
            $('[data-action="export-results"], [data-action="copy-results"], [data-action="regenerate-results"]').prop('disabled', true);
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
            const $trigger = $(document.activeElement);
            const exportType = $trigger.data('export-type') || 'overview';

            if (exportType === 'llm') {
                if (!this.llmTestResults) {
                    this.showNotification('No results to export', 'warning');
                    return;
                }
                this.downloadJSON(this.llmTestResults, `llm_results_${Date.now()}.json`);
                this.showNotification('LLM test results exported successfully', 'success');
                return;
            }

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

        debugLog(message, data = null, type = 'info') {
            const timestamp = new Date().toISOString();
            const entry = data ? { timestamp, message, data } : { timestamp, message };

            if (type === 'error') {
                console.error(message, data);
                this.showDebugPanel();
            } else {
                console.log(message, data);
            }

            let container = $('#rtbcb-debug-log');
            if (!container.length) {
                $('.rtbcb-debug-content').append(`
                    <div class="rtbcb-debug-section">
                        <h4>Logs</h4>
                        <pre id="rtbcb-debug-log" class="rtbcb-code-block"></pre>
                    </div>
                `);
                container = $('#rtbcb-debug-log');
            }

            container.append(document.createTextNode(JSON.stringify(entry) + '\n'));
        },

        showNotification(message, type = 'info') {
            const allowedTypes = ['success', 'error', 'warning', 'info'];
            const safeType = allowedTypes.includes(type) ? type : 'info';
            const safeMessage = $('<div/>').text(message).html();

            // Create notification element
            const notification = $(`
                <div class="rtbcb-notification rtbcb-${safeType}">
                    <span class="dashicons dashicons-${this.getNotificationIcon(safeType)}"></span>
                    <span class="message">${safeMessage}</span>
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
    ,

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
            if (!circuitBreaker.canExecute()) {
                this.showNotification(rtbcbDashboard.strings.error, 'error');
                return;
            }

            const query = $('#rtbcb-rag-query').val().trim();
            const topK = parseInt($('#rtbcb-rag-top-k').val(), 10) || 3;
            const type = $('#rtbcb-rag-type').val();
            if (!query) {
                this.debugLog('RAG test aborted: empty query', null, 'error');
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
                },
                timeout: 60000
            });

            $('#rtbcb-rag-progress').text(rtbcbDashboard.strings.retrieving).show();
            $('[data-action="cancel-rag-test"]').show();
            this.validateRagQuery();

            this.ragRequest.done((res) => {
                if (res.success) {
                    circuitBreaker.recordSuccess();
                    this.displayRagResults(res.data);
                    this.showNotification('Retrieval complete', 'success');
                } else {
                    circuitBreaker.recordFailure();
                    this.showNotification(res.data?.message || rtbcbDashboard.strings.error, 'error');
                }
            }).fail((jqXHR, textStatus, errorThrown) => {
                circuitBreaker.recordFailure();
                const detail = jqXHR?.responseJSON?.data?.detail || errorThrown || textStatus;
                this.showNotification(`${rtbcbDashboard.strings.error}: ${detail}`, 'error');
                console.error('[RAG] Retrieval error:', textStatus, errorThrown, jqXHR?.responseText);
            }).always(() => {
                this.ragRequest = null;
                $('#rtbcb-rag-progress').hide();
                $('[data-action="cancel-rag-test"]').hide();
                this.validateRagQuery();
            });
        },

        runRagTest() {
            const query = $('#rtbcb-rag-query').val().trim();
            const topK = parseInt($('#rtbcb-rag-top-k').val(), 10) || 5;

            this.debugLog('Run RAG Test button clicked', { query, topK });

            if (!query) {
                this.debugLog('RAG test aborted: empty query', null, 'error');
                this.showNotification('Please enter a query', 'error');
                return;
            }

            const button = $('[data-action="run-rag-test"]').prop('disabled', true);
            const payload = {
                action: 'rtbcb_run_rag_test',
                nonce: rtbcbDashboard.nonces.ragTesting,
                queries: [query],
                topK: topK,
                evaluationMode: 'similarity'
            };

            this.debugLog('RAG test payload', payload);

            $.post(rtbcbDashboard.ajaxurl, payload)
                .done((response, textStatus, jqXHR) => {
                    const logData = { status: jqXHR.status, response };
                    if (response.success) {
                        this.debugLog('RAG test success', logData);
                        this.showNotification('RAG test completed', 'success');
                    } else {
                        this.debugLog('RAG test failure response', logData, 'error');
                        this.showNotification(response.data?.message || rtbcbDashboard.strings.error, 'error');
                    }
                })
                .fail((jqXHR, textStatus, errorThrown) => {
                    const detail = jqXHR?.responseJSON?.data?.detail || errorThrown || textStatus;
                    const msg = `${rtbcbDashboard.strings.error}: ${detail}`;
                    this.debugLog('RAG test AJAX error', {
                        status: jqXHR.status,
                        statusText: textStatus,
                        error: errorThrown,
                        response: jqXHR.responseText
                    }, 'error');
                    this.showNotification(msg, 'error');
                })
                .always(() => {
                    button.prop('disabled', false);
                });
        },

        cancelRagQuery() {
            if (this.ragRequest) {
                this.ragRequest.abort();
                this.ragRequest = null;
                $('#rtbcb-rag-progress').hide();
                $('[data-action="cancel-rag-test"]').hide();
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
                $('[data-action="copy-rag-context"], [data-action="export-rag-results"]').prop('disabled', false);
            } else {
                $('#rtbcb-rag-results').hide();
                $('[data-action="copy-rag-context"], [data-action="export-rag-results"]').prop('disabled', true);
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
            const btn = $('[data-action="rebuild-rag-index"]').prop('disabled', true);
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
            }).fail((jqXHR, textStatus, errorThrown) => {
                const detail = jqXHR?.responseJSON?.data?.detail || errorThrown || textStatus;
                const msg = `${rtbcbDashboard.strings.rebuildFailed}: ${detail}`;
                $('#rtbcb-rag-index-notice').text(msg);
                this.showNotification(msg, 'error');
                console.error('[RAG] Index rebuild error:', textStatus, errorThrown, jqXHR?.responseText);
            }).always(() => {
                btn.prop('disabled', false);
            });
        },

        // ROI Calculator helpers
        loadRoiScenario(scenario) {
            const preset = this.roiPresets[scenario];
            if (!preset) {
                return;
            }
            Object.keys(preset).forEach(id => {
                const value = preset[id];
                const el = document.getElementById(id);
                if (el) {
                    $(el).val(value);
                }
            });
        },

        calculateRoiTest() {
            const button = $('[data-action="calculate-roi"]').prop('disabled', true);
            const roiData = {};
            $('#roi-calculator').find('input, select').each(function() {
                roiData[this.id] = $(this).val();
            });

            this.request('calculate_roi_test', { roi_data: roiData })
                .then(data => {
                    this.renderRoiResults(data);
                    this.showNotification('ROI calculated', 'success');
                })
                .catch(err => {
                    console.error('[ROI Test] error:', err);
                    this.showNotification(err.message || rtbcbDashboard.strings.error, 'error');
                })
                .finally(() => {
                    button.prop('disabled', false);
                });
        },

        renderRoiResults(data) {
            const scenarios = {
                conservative: data.conservative || {},
                base: data.base || {},
                optimistic: data.optimistic || {}
            };

            const formatCurrency = val => '$' + Number(val || 0).toLocaleString();
            const formatPercent = val => (Number(val || 0)).toFixed(1) + '%';

            $('#roi-conservative-percent').text(formatPercent(scenarios.conservative.roi_percentage));
            $('#roi-conservative-amount').text(formatCurrency(scenarios.conservative.total_annual_benefit));

            $('#roi-realistic-percent').text(formatPercent(scenarios.base.roi_percentage));
            $('#roi-realistic-amount').text(formatCurrency(scenarios.base.total_annual_benefit));

            $('#roi-optimistic-percent').text(formatPercent(scenarios.optimistic.roi_percentage));
            $('#roi-optimistic-amount').text(formatCurrency(scenarios.optimistic.total_annual_benefit));

            const labels = ['Conservative', 'Realistic', 'Optimistic'];
            const roiValues = [
                scenarios.conservative.roi_percentage,
                scenarios.base.roi_percentage,
                scenarios.optimistic.roi_percentage
            ];

            this.createChart('roi-comparison-chart', {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'ROI %',
                        data: roiValues,
                        backgroundColor: ['#d63638', '#007cba', '#2ecc71']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: value => value + '%' } }
                    }
                }
            });

            const breakdown = [
                scenarios.base.labor_savings,
                scenarios.base.fee_savings,
                scenarios.base.error_reduction
            ];

            this.createChart('roi-breakdown-chart', {
                type: 'pie',
                data: {
                    labels: ['Labor', 'Fees', 'Errors'],
                    datasets: [{
                        data: breakdown,
                        backgroundColor: ['#007cba', '#00a0d2', '#46b450']
                    }]
                },
                options: { responsive: true }
            });

            const analysis = data.analysis || {};
            const analysisText = `Recommendation: ${analysis.recommendation || 'n/a'} (Confidence: ${analysis.confidence || 'n/a'})`;
            if (!$('#roi-analysis-summary').length) {
                $('#roi-results-container .rtbcb-results-header').append('<p id="roi-analysis-summary"></p>');
            }
            $('#roi-analysis-summary').text(analysisText);

            this.lastRoiResults = {
                scenarios: scenarios,
                analysis: analysis,
                input_summary: data.input_summary || {}
            };

            $('#roi-results-container').show();
            $('[data-action="export-roi-results"]').prop('disabled', false);
        },

        exportRoiResults() {
            if (!this.lastRoiResults) {
                this.showNotification('No ROI results to export', 'warning');
                return;
            }

            this.downloadJSON(this.lastRoiResults, `roi_results_${Date.now()}.json`);
            this.showNotification('ROI results exported', 'success');
        },

        // API Health Methods
        initApiHealth() {
            this.apiResults = rtbcbDashboard.apiHealth?.lastResults?.results || {};
            this.updateApiSummary();
        },

        runAllApiTests() {
            console.log('[API Health] === runAllApiTests start ===');

            // Check prerequisites
            if (!rtbcbDashboard.ajaxurl) {
                console.error('[API Health] AJAX URL not available');
                this.showNotification('System configuration error: AJAX URL missing', 'error');
                return;
            }

            if (!rtbcbDashboard.nonces || !rtbcbDashboard.nonces.apiHealth) {
                console.error('[API Health] Security nonce not available');
                this.showNotification('System configuration error: Security nonce missing', 'error');
                return;
            }

            // Check circuit breaker
            if (!circuitBreaker.canExecute()) {
                console.error('[API Health] Circuit breaker open. Failures:', circuitBreaker.failures);
                this.showNotification('Too many recent failures. Please wait before retrying.', 'warning');
                return;
            }

            const $button = $('[data-action="api-health-ping"]').prop('disabled', true);
            $('#rtbcb-api-health-notice').text('Running comprehensive API health tests...');

            console.log('[API Health] Making AJAX request...');

            $.ajax({
                url: rtbcbDashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rtbcb_run_api_health_tests',
                    nonce: rtbcbDashboard.nonces.apiHealth
                },
                timeout: 120000, // 2 minutes

                beforeSend: function(xhr) {
                    console.log('[API Health] Request initiated');
                }
            }).done((response) => {
                console.log('[API Health] Request completed successfully', response);

                if (response.success) {
                    circuitBreaker.recordSuccess();
                    const data = response.data;
                    this.apiResults = data.results;

                    Object.keys(data.results).forEach(key => {
                        this.updateApiRow(key, data.results[key], data.timestamp);
                    });

                    this.updateApiSummary();
                    this.showNotification('API health tests completed successfully', 'success');
                } else {
                    circuitBreaker.recordFailure();
                    const errMsg = response?.data?.message || 'API tests failed';
                    console.error('[API Health] Server returned error:', errMsg, response);
                    $('#rtbcb-api-health-notice').text(errMsg);
                    this.showNotification(errMsg, 'error');
                }
            }).fail((jqXHR, textStatus, errorThrown) => {
                circuitBreaker.recordFailure();

                console.error('[API Health] AJAX request failed:');
                console.error('Status:', jqXHR.status);
                console.error('Status Text:', jqXHR.statusText);
                console.error('Response Text:', jqXHR.responseText);
                console.error('Text Status:', textStatus);
                console.error('Error Thrown:', errorThrown);

                let errorMessage = 'API health test request failed';

                if (jqXHR.status === 0) {
                    errorMessage = 'Network connection error - check internet connectivity';
                } else if (jqXHR.status === 403) {
                    errorMessage = 'Permission denied - security check failed';
                } else if (jqXHR.status === 500) {
                    errorMessage = 'Server error - check PHP error logs';
                } else if (textStatus === 'timeout') {
                    errorMessage = 'Request timed out - server may be overloaded';
                } else {
                    errorMessage = `Request failed (${jqXHR.status}: ${errorThrown})`;
                }

                $('#rtbcb-api-health-notice').text(errorMessage);
                this.showNotification(errorMessage, 'error');
            }).always(() => {
                $button.prop('disabled', false);
                console.log('[API Health] === runAllApiTests complete ===');
            });
        },

        runSingleApiTest(component) {
            console.log(`[API Health] Initiating API test for ${component}`);
            if (!circuitBreaker.canExecute()) {
                console.error('[API Health] Circuit breaker open. Aborting API test for', component);
                this.showNotification(rtbcbDashboard.strings.error, 'error');
                return;
            }
            const button = $(`.rtbcb-retest[data-component="${component}"]`).prop('disabled', true);
            $.ajax({
                url: rtbcbDashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rtbcb_run_single_api_test',
                    nonce: rtbcbDashboard.nonces.apiHealth,
                    component
                },
                timeout: 60000
            }).done((response) => {
                if (response.success) {
                    circuitBreaker.recordSuccess();
                    console.log(`[API Health] API test for ${component} succeeded`, response.data);
                    const res = response.data.result;
                    this.apiResults[component] = res;
                    this.updateApiRow(component, res, response.data.timestamp);
                    this.updateApiSummary();
                } else {
                    circuitBreaker.recordFailure();
                    const errMsg = response?.data?.message || rtbcbDashboard.strings.error;
                    console.error(`[API Health] API test for ${component} failed:`, errMsg, response);
                    $('#rtbcb-api-health-notice').text(rtbcbDashboard.strings.error);
                }
            }).fail((jqXHR, textStatus, errorThrown) => {
                circuitBreaker.recordFailure();

                console.error(`[API Health] Request error during API test for ${component} - status:`, jqXHR.status);
                console.error(`[API Health] Request error during API test for ${component} - statusText:`, jqXHR.statusText);
                console.error(`[API Health] Request error during API test for ${component} - responseText:`, jqXHR.responseText);
                console.error(`[API Health] Request error during API test for ${component} - textStatus:`, textStatus);
                console.error(`[API Health] Request error during API test for ${component} - errorThrown:`, errorThrown);

                let parsedMessage = '';
                try {
                    const parsed = JSON.parse(jqXHR.responseText || '{}');
                    parsedMessage = parsed?.data?.detail || parsed?.message || jqXHR.responseText;
                } catch (e) {
                    parsedMessage = (jqXHR.responseText || '').trim();
                }
                const detail = parsedMessage || errorThrown || textStatus;
                const msg = `${rtbcbDashboard.strings.error}: ${detail}`;

                this.showNotification(msg, 'error');
                $('#rtbcb-api-health-notice').text(msg);
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
            const msg = result.message || result.details?.message || '';
            row.find('.rtbcb-message').text(msg);
            $(`#rtbcb-details-${component} pre`).text(JSON.stringify(result.details || {}, null, 2));
        },

        toggleApiDetails(component) {
            $(`#rtbcb-details-${component}`).toggle();
        },

        updateApiSummary() {
            const results = this.apiResults || {};
            const failures = Object.values(results).filter(r => !r.passed);
            let message;
            if (Object.keys(results).length === 0) {
                message = rtbcbDashboard.strings.notTested;
            } else if (failures.length === 0) {
                message = rtbcbDashboard.strings.allOperational;
            } else {
                const failureMessages = failures
                    .map(r => r.message || r.details?.message)
                    .filter(Boolean);
                message = rtbcbDashboard.strings.errorsDetected.replace('%d', failures.length);
                if (failureMessages.length) {
                    message += ': ' + failureMessages.join('; ');
                }
            }
            $('#rtbcb-api-health-notice').text(message);
        },

        // Run data health checks
        runDataHealthChecks() {
            const button = $('[data-action="run-data-health"]').prop('disabled', true);
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
            }).fail((jqXHR, textStatus, errorThrown) => {
                const detail = jqXHR?.responseJSON?.data?.detail || errorThrown || textStatus;
                const msg = `${rtbcbDashboard.strings.error}: ${detail}`;
                const safeMsg = $('<div/>').text(msg).html();
                $('#rtbcb-data-health-results').html(`<tr><td colspan="3">${safeMsg}</td></tr>`);
                this.showNotification(msg, 'error');
                console.error('[Data Health] Request error:', textStatus, errorThrown, jqXHR?.responseText);
            }).always(() => {
                button.prop('disabled', false);
            });
        },

        // Generate report preview
        generatePreviewReport() {
            const button = $('[data-action="generate-preview-report"]').prop('disabled', true);

            $.post(rtbcbDashboard.ajaxurl, {
                action: 'rtbcb_generate_preview_report',
                nonce: rtbcbDashboard.nonces.reportPreview
            }).done((response) => {
                if (response.success) {
                    $('#rtbcb-report-preview-frame').attr('srcdoc', response.data.html || '');
                } else {
                    this.showNotification(response.data?.message || rtbcbDashboard.strings.error, 'error');
                }
            }).fail((jqXHR, textStatus, errorThrown) => {
                const detail = jqXHR?.responseJSON?.data?.detail || errorThrown || textStatus;
                const msg = `${rtbcbDashboard.strings.error}: ${detail}`;
                this.showNotification(msg, 'error');
                console.error('[Report Preview] AJAX error:', textStatus, errorThrown, jqXHR?.responseText);
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
                nonce: rtbcbDashboard.nonces.saveSettings || $form.find('[name="nonce"]').val(),
                rtbcb_openai_api_key: $('#rtbcb_openai_api_key').val(),
                rtbcb_mini_model: $('#rtbcb_mini_model').val(),
                rtbcb_premium_model: $('#rtbcb_premium_model').val(),
                rtbcb_advanced_model: $('#rtbcb_advanced_model').val(),
                rtbcb_embedding_model: $('#rtbcb_embedding_model').val()
            };

            console.log('[RTBCB] Saving dashboard settings', data);

            const $button = $form.find('button[type="submit"]').prop('disabled', true);

            $.post(rtbcbDashboard.ajaxurl, data).done((response) => {
                console.log('[RTBCB] Save settings response', response);
                if (response.success) {
                    this.showNotification(rtbcbDashboard.strings.settingsSaved, 'success');
                } else {
                    this.showNotification(response.data?.message || rtbcbDashboard.strings.error, 'error');
                }
            }).fail((jqXHR, textStatus, errorThrown) => {
                const detail = jqXHR?.responseJSON?.data?.detail || errorThrown || textStatus;
                const msg = `${rtbcbDashboard.strings.error}: ${detail}`;
                console.error('[RTBCB] Save settings AJAX error', textStatus, errorThrown, jqXHR?.responseText);
                this.showNotification(msg, 'error');
            }).always(() => {
                $button.prop('disabled', false);
            });
        },

        // Utility function for HTML escaping
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    Dashboard.getNonceKeyForAction = function(action) {
        const actionNonceMap = {
            'test_company_overview_enhanced': 'dashboard',
            'test_llm_model': 'llm',
            'run_llm_test': 'llm',
            'test_rag_query': 'dashboard',
            'run_rag_test': 'ragTesting',
            'run_api_health_tests': 'apiHealth',
            'api_health_ping': 'apiHealth',
            'calculate_roi_test': 'roiCalculator',
            'run_data_health_checks': 'dataHealth',
            'generate_preview_report': 'reportPreview',
            'save_dashboard_settings': 'saveSettings'
        };
        return actionNonceMap[action] || 'dashboard';
    };

    // Unified Result Store
    Dashboard.ResultStore = {
        data: {},

        // Store result with standardized schema
        store: function(moduleType, testId, resultData) {
            if (!this.data[moduleType]) {
                this.data[moduleType] = {};
            }

            this.data[moduleType][testId] = {
                ...resultData,
                metadata: {
                    moduleType: moduleType,
                    testId: testId,
                    timestamp: new Date().toISOString(),
                    version: '1.0',
                    ...resultData.metadata
                }
            };

            this.persistToStorage();
            $(document).trigger('rtbcb:result-stored', [moduleType, testId, resultData]);
        },

        // Get all results or filter by module
        getResults: function(moduleType = null) {
            if (moduleType) {
                return this.data[moduleType] || {};
            }
            return this.data;
        },

        // Export to JSON
        exportJSON: function() {
            const exportData = {
                exportMetadata: {
                    timestamp: new Date().toISOString(),
                    plugin: 'Real Treasury Business Case Builder',
                    version: '2.0.0',
                    dashboard: 'unified-test-dashboard'
                },
                results: this.data,
                summary: this.generateSummary()
            };

            return JSON.stringify(exportData, null, 2);
        },

        // Export to CSV
        exportCSV: function() {
            const rows = [];
            const headers = ['Module', 'Test ID', 'Timestamp', 'Status', 'Duration', 'Tokens Used', 'Cost', 'Quality Score', 'Details'];
            rows.push(headers);

            Object.keys(this.data).forEach(moduleType => {
                Object.keys(this.data[moduleType]).forEach(testId => {
                    const result = this.data[moduleType][testId];
                    const row = [
                        moduleType,
                        testId,
                        result.metadata.timestamp,
                        result.status || 'completed',
                        result.duration || '',
                        result.tokens_used || '',
                        result.cost_estimate || '',
                        result.quality_score || '',
                        JSON.stringify(result.summary || {})
                    ];
                    rows.push(row);
                });
            });

            return rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n');
        },

        // Generate summary statistics
        generateSummary: function() {
            const summary = {
                modules_tested: Object.keys(this.data).length,
                total_tests: 0,
                total_cost: 0,
                total_tokens: 0,
                avg_quality: 0,
                test_counts_by_module: {}
            };

            let qualitySum = 0;
            let qualityCount = 0;

            Object.keys(this.data).forEach(moduleType => {
                const moduleResults = this.data[moduleType];
                const moduleTestCount = Object.keys(moduleResults).length;
                summary.total_tests += moduleTestCount;
                summary.test_counts_by_module[moduleType] = moduleTestCount;

                Object.values(moduleResults).forEach(result => {
                    if (result.cost_estimate) summary.total_cost += parseFloat(result.cost_estimate);
                    if (result.tokens_used) summary.total_tokens += parseInt(result.tokens_used);
                    if (result.quality_score) {
                        qualitySum += parseFloat(result.quality_score);
                        qualityCount++;
                    }
                });
            });

            summary.avg_quality = qualityCount > 0 ? (qualitySum / qualityCount).toFixed(1) : 0;
            summary.total_cost = summary.total_cost.toFixed(4);

            return summary;
        },

        // Persist to localStorage
        persistToStorage: function() {
            try {
                localStorage.setItem('rtbcb_test_results', JSON.stringify(this.data));
            } catch (e) {
                console.warn('Failed to persist results to storage:', e);
            }
        },

        // Load from localStorage
        loadFromStorage: function() {
            try {
                const stored = localStorage.getItem('rtbcb_test_results');
                if (stored) {
                    this.data = JSON.parse(stored);
                }
            } catch (e) {
                console.warn('Failed to load results from storage:', e);
                this.data = {};
            }
        },

        // Clear all results
        clear: function() {
            this.data = {};
            this.persistToStorage();
            $(document).trigger('rtbcb:results-cleared');
        }
    };

    Dashboard.circuitBreaker = circuitBreaker;

    // Initialize result store
    Dashboard.ResultStore.loadFromStorage();

    // Example usage in test completion handlers:
    // Dashboard.ResultStore.store('llm', 'model_comparison_' + Date.now(), {
    //     results: results,
    //     summary: summary,
    //     status: 'completed',
    //     duration: totalTime,
    //     tokens_used: totalTokens,
    //     cost_estimate: totalCost,
    //     quality_score: avgQuality
    // });

    // Initialize when DOM is ready
    $(document).ready(function() {
        Dashboard.init();
    });

    // Expose Dashboard object for debugging
    window.RTBCBDashboard = Dashboard;
    window.Dashboard = Dashboard;

    window.DashboardDiag = {
        assertNonce: function(action) {
            const nonces = rtbcbDashboard?.nonces || {};
            const found = !!nonces[action];
            console.log(`[DIAG] Nonce '${action}':`, found ? 'FOUND' : 'MISSING');
            if (!found) console.warn('[DIAG] Available nonces:', Object.keys(nonces));
            return found;
        },

        assertTabVisibility: function() {
            const activeTab = $('.rtbcb-test-section.active:visible');
            console.log('[DIAG] Active visible tabs:', activeTab.length);
            console.log('[DIAG] Current tab ID:', activeTab.attr('id'));
            return activeTab.length > 0;
        },

        countHandlers: function(selector) {
            const el = $(selector)[0];
            if (!el) return 0;
            const events = $._data(el, 'events') || {};
            const count = Object.keys(events).reduce((sum, type) => sum + events[type].length, 0);
            console.log(`[DIAG] Handlers on '${selector}':`, count, events);
            return count;
        },

        checkOverlays: function() {
            const overlays = $('.rtbcb-loading, [style*="z-index"]').filter(':visible');
            overlays.each(function() {
                const zIndex = $(this).css('z-index');
                const pointerEvents = $(this).css('pointer-events');
                console.log('[DIAG] Overlay:', this.className, `z-index:${zIndex}, pointer-events:${pointerEvents}`);
            });
            return overlays.length;
        }
    };

    // Auto-diagnose button clicks
    $(document).on('click', '[data-action]', function(e) {
        const action = $(this).data('action');
        console.log(`[DIAG] Button click: ${action}, disabled:${$(this).prop('disabled')}`);
        window.DashboardDiag.assertNonce(action.replace('run-', '').replace('-test', ''));
    });

})(jQuery);

