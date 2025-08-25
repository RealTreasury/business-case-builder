/**
 * Fixed Unified Test Dashboard JavaScript
 * Handles all dashboard functionality with improved error handling and state management
 */
(function($) {
    'use strict';

    // Early validation
    if (typeof rtbcbDashboard === 'undefined') {
        console.error('rtbcbDashboard is not defined');
        return;
    }

    console.log('Test dashboard script loaded');

    // Utility functions
    const debounce = (func, delay) => {
        let timeoutId;
        return (...args) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    };

    // Circuit breaker for API failures
    const circuitBreaker = {
        failures: 0,
        threshold: parseInt(rtbcbDashboard.circuitBreaker?.threshold || 5, 10),
        resetTime: parseInt(rtbcbDashboard.circuitBreaker?.resetTime || 60000, 10),
        lastFailTime: 0,

        canExecute() {
            if (this.failures < this.threshold) return true;
            
            const now = Date.now();
            if (now - this.lastFailTime > this.resetTime) {
                this.reset();
                return true;
            }
            return false;
        },

        recordFailure() {
            this.failures++;
            this.lastFailTime = Date.now();
            console.warn(`[Circuit Breaker] Failure ${this.failures}/${this.threshold}`);
        },

        recordSuccess() {
            this.failures = 0;
        },

        reset() {
            this.failures = 0;
            console.log('[Circuit Breaker] Reset');
        }
    };

    // Main Dashboard object
    const Dashboard = {
        currentTab: 'company-overview',
        isGenerating: false,
        progressTimer: null,
        startTime: null,
        currentRequest: null,
        charts: {},

        // Initialize dashboard
        init() {
            console.log('Dashboard initializing...');
            
            try {
                this.bindEvents();
                this.initializeTabs();
                this.setupValidation();
                this.loadSavedState();
                
                // Initialize Chart.js if available
                if (typeof Chart !== 'undefined') {
                    this.setupCharts();
                }
                
                console.log('Dashboard initialized successfully');
            } catch (error) {
                console.error('Dashboard initialization failed:', error);
                this.showNotification('Dashboard initialization failed. Please refresh the page.', 'error');
            }
        },

        // Bind all event handlers
        bindEvents() {
            console.log('Binding events...');
            
            // Remove any existing handlers to prevent duplicates
            $(document).off('.rtbcb-dashboard');
            
            // Store reference to Dashboard object for event handlers
            const self = this;
            
            // Tab navigation
            $(document).on('click.rtbcb-dashboard', '.rtbcb-test-tabs .nav-tab', function(e) {
                e.preventDefault();
                const tab = $(e.currentTarget).data('tab');
                if (tab) {
                    self.switchTab(tab);
                }
            });

            // Company Overview actions
            $(document).on('click.rtbcb-dashboard', '[data-action="run-company-overview"]', function(e) {
                e.preventDefault();
                self.generateCompanyOverview();
            });

            $(document).on('click.rtbcb-dashboard', '[data-action="clear-results"]', function(e) {
                e.preventDefault();
                self.clearResults();
            });

            // LLM Test actions
            $(document).on('click.rtbcb-dashboard', '[data-action="run-llm-test"]', function(e) {
                e.preventDefault();
                self.runLLMTest();
            });

            // RAG System actions
            $(document).on('click.rtbcb-dashboard', '[data-action="run-rag-test"]', function(e) {
                e.preventDefault();
                self.runRagTest();
            });

            $(document).on('click.rtbcb-dashboard', '[data-action="rebuild-rag-index"]', function(e) {
                e.preventDefault();
                self.rebuildRagIndex();
            });

            // API Health actions
            $(document).on('click.rtbcb-dashboard', '[data-action="api-health-ping"]', function(e) {
                e.preventDefault();
                self.runAllApiTests();
            });

            // ROI Calculator actions
            $(document).on('click.rtbcb-dashboard', '[data-action="calculate-roi"]', function(e) {
                e.preventDefault();
                self.calculateROI();
            });

            // Settings actions
            $(document).on('submit.rtbcb-dashboard', '#rtbcb-dashboard-settings-form', function(e) {
                e.preventDefault();
                self.saveSettings();
            });

            $(document).on('click.rtbcb-dashboard', '[data-action="toggle-api-key"]', function(e) {
                e.preventDefault();
                self.toggleApiKeyVisibility();
            });

            // Input validation
            $(document).on('input.rtbcb-dashboard', '#company-name-input', debounce(function() {
                self.validateCompanyInput();
            }, 300));

            // Temperature slider
            $(document).on('input.rtbcb-dashboard', '#llm-temperature', function(e) {
                $('#llm-temperature-value').text($(e.target).val());
            });

            // Model selection
            $(document).on('change.rtbcb-dashboard', 'input[name="test-models[]"]', function() {
                self.validateLLMInputs();
            });

            // RAG query input
            $(document).on('input.rtbcb-dashboard', '#rtbcb-rag-query', debounce(function() {
                self.validateRagQuery();
            }, 300));

            console.log('Events bound successfully');
        },

        // Initialize tab system
        initializeTabs() {
            const hash = window.location.hash.replace('#', '');
            const validTabs = [
                'company-overview', 'roi-calculator', 'llm-tests', 
                'rag-system', 'api-health', 'data-health', 
                'report-preview', 'settings'
            ];
            
            const targetTab = validTabs.includes(hash) ? hash : 'company-overview';
            this.switchTab(targetTab);
        },

        // Switch to specific tab
        switchTab(tabName) {
            if (this.isGenerating) {
                this.showNotification('Cannot switch tabs while operation is in progress', 'warning');
                return false;
            }

            console.log(`Switching to tab: ${tabName}`);

            try {
                // Update navigation
                $('.rtbcb-test-tabs .nav-tab').removeClass('nav-tab-active');
                $(`.rtbcb-test-tabs .nav-tab[data-tab="${tabName}"]`).addClass('nav-tab-active');

                // Hide all sections
                $('.rtbcb-test-section').removeClass('active').hide();
                
                // Show target section
                const $targetSection = $(`#${tabName}`);
                if ($targetSection.length) {
                    $targetSection.addClass('active').show();
                    this.currentTab = tabName;
                    
                    // Update URL hash
                    window.location.hash = tabName;
                    
                    // Initialize tab-specific functionality
                    this.initializeTabContent(tabName);
                    
                    return true;
                } else {
                    console.error(`Tab section not found: ${tabName}`);
                    return false;
                }
            } catch (error) {
                console.error('Error switching tabs:', error);
                this.showNotification('Error switching tabs', 'error');
                return false;
            }
        },

        // Initialize content for specific tabs
        initializeTabContent(tabName) {
            switch (tabName) {
                case 'company-overview':
                    this.validateCompanyInput();
                    break;
                case 'llm-tests':
                    this.validateLLMInputs();
                    break;
                case 'rag-system':
                    this.validateRagQuery();
                    break;
                case 'api-health':
                    this.updateApiHealthStatus();
                    break;
            }
        },

        // Setup form validation
        setupValidation() {
            this.validateCompanyInput();
            this.validateLLMInputs();
            this.validateRagQuery();
        },

        // Load saved state
        loadSavedState() {
            // Load any saved form data or states
            const savedCompany = localStorage.getItem('rtbcb_company_name');
            if (savedCompany) {
                $('#company-name-input').val(savedCompany);
                this.validateCompanyInput();
            }
        },

        // Company Overview functionality
        generateCompanyOverview() {
            if (this.isGenerating) return;

            const companyName = $('#company-name-input').val().trim();
            if (!companyName) {
                this.showNotification('Please enter a company name', 'error');
                return;
            }

            if (!circuitBreaker.canExecute()) {
                this.showNotification('Too many failures. Please wait before trying again.', 'warning');
                return;
            }

            console.log(`Generating overview for: ${companyName}`);

            this.isGenerating = true;
            this.setButtonState('[data-action="run-company-overview"]', 'loading');
            this.startProgress();

            // Save company name
            localStorage.setItem('rtbcb_company_name', companyName);

            const requestData = {
                action: 'rtbcb_test_company_overview_enhanced',
                nonce: rtbcbDashboard.nonces?.dashboard || '',
                company_name: companyName,
                model: $('#model-selection').val() || 'mini',
                show_debug: $('#show-debug-info').is(':checked')
            };

            this.makeRequest(requestData)
                .then(response => {
                    circuitBreaker.recordSuccess();
                    this.displayCompanyResults(response);
                    this.setButtonState('[data-action="run-company-overview"]', 'success');
                })
                .catch(error => {
                    circuitBreaker.recordFailure();
                    console.error('Company overview error:', error);
                    this.showError(error.message || 'Failed to generate overview');
                    this.setButtonState('[data-action="run-company-overview"]', 'error');
                })
                .finally(() => {
                    this.isGenerating = false;
                    this.stopProgress();
                });
        },

        // LLM Test functionality
        runLLMTest() {
            if (this.isGenerating) return;

            const prompt = $('#llm-test-prompt').val().trim();
            const selectedModels = $('input[name="test-models[]"]:checked').map(function() {
                return $(this).val();
            }).get();

            if (!prompt || selectedModels.length === 0) {
                this.showNotification('Please enter a prompt and select at least one model', 'error');
                return;
            }

            console.log('Running LLM test...', { prompt, models: selectedModels });

            this.isGenerating = true;
            this.setButtonState('[data-action="run-llm-test"]', 'loading');

            const requestData = {
                action: 'rtbcb_run_llm_test',
                nonce: rtbcbDashboard.nonces?.llm || '',
                modelIds: selectedModels,
                promptA: prompt,
                maxTokens: parseInt($('#llm-max-tokens').val()) || 1000,
                temperature: parseFloat($('#llm-temperature').val()) || 0.3,
                runMode: 'matrix'
            };

            this.makeRequest(requestData)
                .then(response => {
                    this.displayLLMResults(response);
                    this.setButtonState('[data-action="run-llm-test"]', 'success');
                })
                .catch(error => {
                    console.error('LLM test error:', error);
                    this.showError(error.message || 'LLM test failed');
                    this.setButtonState('[data-action="run-llm-test"]', 'error');
                })
                .finally(() => {
                    this.isGenerating = false;
                });
        },

        // RAG System functionality
        runRagTest() {
            const query = $('#rtbcb-rag-query').val().trim();
            if (!query) {
                this.showNotification('Please enter a query', 'error');
                return;
            }

            console.log('Running RAG test...', { query });

            this.setButtonState('[data-action="run-rag-test"]', 'loading');

            const requestData = {
                action: 'rtbcb_test_rag_query',
                nonce: rtbcbDashboard.nonces?.dashboard || '',
                query: query,
                top_k: parseInt($('#rtbcb-rag-top-k').val()) || 5,
                type: $('#rtbcb-rag-type').val() || 'all'
            };

            this.makeRequest(requestData)
                .then(response => {
                    this.displayRagResults(response);
                    this.setButtonState('[data-action="run-rag-test"]', 'success');
                })
                .catch(error => {
                    console.error('RAG test error:', error);
                    this.showError(error.message || 'RAG test failed');
                    this.setButtonState('[data-action="run-rag-test"]', 'error');
                });
        },

        rebuildRagIndex() {
            console.log('Rebuilding RAG index...');

            this.setButtonState('[data-action="rebuild-rag-index"]', 'loading');

            const requestData = {
                action: 'rtbcb_rag_rebuild_index',
                nonce: rtbcbDashboard.nonces?.dashboard || ''
            };

            this.makeRequest(requestData)
                .then(response => {
                    this.showNotification('RAG index rebuilt successfully', 'success');
                    this.setButtonState('[data-action="rebuild-rag-index"]', 'success');
                    // Update index info
                    if (response.index_size) {
                        $('#rtbcb-rag-index-size').text(`Entries: ${response.index_size}`);
                    }
                    if (response.last_indexed) {
                        $('#rtbcb-rag-last-indexed').text(`Last indexed: ${response.last_indexed}`);
                    }
                })
                .catch(error => {
                    console.error('RAG rebuild error:', error);
                    this.showError(error.message || 'RAG index rebuild failed');
                    this.setButtonState('[data-action="rebuild-rag-index"]', 'error');
                });
        },

        // API Health functionality
        runAllApiTests() {
            console.log('Running API health tests...');

            this.setButtonState('[data-action="api-health-ping"]', 'loading');
            $('#rtbcb-api-health-notice').text('Running comprehensive API tests...');

            const requestData = {
                action: 'rtbcb_run_api_health_tests',
                nonce: rtbcbDashboard.nonces?.apiHealth || ''
            };

            this.makeRequest(requestData)
                .then(response => {
                    this.updateApiHealthResults(response);
                    this.setButtonState('[data-action="api-health-ping"]', 'success');
                    this.showNotification('API health tests completed', 'success');
                })
                .catch(error => {
                    console.error('API health test error:', error);
                    this.showError(error.message || 'API health tests failed');
                    this.setButtonState('[data-action="api-health-ping"]', 'error');
                });
        },

        // ROI Calculator functionality
        calculateROI() {
            console.log('Calculating ROI...');

            this.setButtonState('[data-action="calculate-roi"]', 'loading');

            // Collect ROI form data
            const roiData = {};
            $('#roi-calculator').find('input, select').each(function() {
                if (this.id) {
                    roiData[this.id] = $(this).val();
                }
            });

            const requestData = {
                action: 'rtbcb_calculate_roi_test',
                nonce: rtbcbDashboard.nonces?.roiCalculator || '',
                roi_data: roiData
            };

            this.makeRequest(requestData)
                .then(response => {
                    this.displayROIResults(response);
                    this.setButtonState('[data-action="calculate-roi"]', 'success');
                    this.showNotification('ROI calculated successfully', 'success');
                })
                .catch(error => {
                    console.error('ROI calculation error:', error);
                    this.showError(error.message || 'ROI calculation failed');
                    this.setButtonState('[data-action="calculate-roi"]', 'error');
                });
        },

        // Settings functionality
        saveSettings() {
            console.log('Saving settings...');

            const formData = new FormData(document.getElementById('rtbcb-dashboard-settings-form'));
            const requestData = {
                action: 'rtbcb_save_dashboard_settings',
                nonce: formData.get('nonce') || rtbcbDashboard.nonces?.saveSettings
            };

            // Add all form fields to request data
            for (let [key, value] of formData.entries()) {
                requestData[key] = value;
            }

            this.makeRequest(requestData)
                .then((response) => {
                    if (response.reload) {
                        window.location.reload();
                        return;
                    }

                    const isValid = !!response.api_valid;
                    rtbcbDashboard.api_valid = isValid;
                    this.updateApiKeyStatus(isValid);
                    $('[data-action="run-company-overview"]').prop('disabled', !isValid);

                    if (isValid) {
                        this.showNotification(
                            response.message || rtbcbDashboard.strings?.validApiKeySaved || 'Valid API key saved',
                            'success'
                        );
                    } else {
                        this.showError(
                            response.message || rtbcbDashboard.strings?.apiKeyValidationFailed || 'API key validation failed'
                        );
                    }
                })
                .catch((error) => {
                    console.error('Settings save error:', error);
                    this.showError(error.message || rtbcbDashboard.strings?.settingsSaveFailed || 'Failed to save settings');
                });
        },

        toggleApiKeyVisibility() {
            const $input = $('#rtbcb_openai_api_key');
            const $button = $('[data-action="toggle-api-key"]');
            
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $button.text('Hide');
            } else {
                $input.attr('type', 'password');
                $button.text('Show');
            }
        },

        updateApiKeyStatus(isValid) {
            const $status = $('#rtbcb-api-key-status');
            $status.toggleClass('status-good', isValid);
            $status.toggleClass('status-error', !isValid);
            $status.find('.dashicons')
                .toggleClass('dashicons-yes-alt', isValid)
                .toggleClass('dashicons-warning', !isValid);
            $status.find('.status-text').text(isValid ?
                (rtbcbDashboard.strings?.valid || 'Valid') :
                (rtbcbDashboard.strings?.invalid || 'Invalid')
            );
        },

        // Validation methods
        validateCompanyInput() {
            const companyName = $('#company-name-input').val().trim();
            const isValid = companyName.length >= 2;
            
            $('[data-action="run-company-overview"]').prop('disabled', !isValid || this.isGenerating);
            
            if (companyName.length > 0 && companyName.length < 2) {
                $('#company-name-input').addClass('error');
            } else {
                $('#company-name-input').removeClass('error');
            }
        },

        validateLLMInputs() {
            const prompt = $('#llm-test-prompt').val().trim();
            const selectedModels = $('input[name="test-models[]"]:checked').length;
            
            const isValid = prompt.length > 0 && selectedModels > 0;
            $('[data-action="run-llm-test"]').prop('disabled', !isValid || this.isGenerating);
        },

        validateRagQuery() {
            const query = $('#rtbcb-rag-query').val().trim();
            const isValid = query.length > 0;
            
            $('[data-action="run-rag-test"]').prop('disabled', !isValid || this.isGenerating);
        },

        // Display methods
        displayCompanyResults(data) {
            const $container = $('#results-container');
            const $content = $('#results-content');
            const $meta = $('#results-meta');

            if (data.overview) {
                $content.html(this.formatContent(data.overview));
            }

            // Build metadata display
            const metaItems = [];
            if (data.word_count) metaItems.push(`Words: ${data.word_count}`);
            if (data.elapsed) metaItems.push(`Time: ${data.elapsed}s`);
            if (data.model_used) metaItems.push(`Model: ${data.model_used}`);
            
            $meta.html(metaItems.join(' | '));
            $container.show();

            // Enable action buttons
            $('[data-action="clear-results"], [data-action="export-results"]').prop('disabled', false);
        },

        displayLLMResults(data) {
            console.log('Displaying LLM results:', data);
            
            const $container = $('#llm-test-results');
            const $tbody = $('#llm-comparison-tbody');
            
            $tbody.empty();
            
            if (data.results && Array.isArray(data.results)) {
                data.results.forEach(result => {
                    const row = $(`
                        <tr class="rtbcb-result-row">
                            <td><strong>${this.escapeHtml(result.model_name || result.model_key)}</strong></td>
                            <td>${result.latency || result.response_time || '--'}ms</td>
                            <td>${result.tokens_used || '--'}</td>
                            <td>$${(result.cost_estimate || 0).toFixed(6)}</td>
                            <td>${result.quality_score || '--'}</td>
                            <td class="rtbcb-response-preview">${this.escapeHtml((result.response || result.content || '').substring(0, 100))}...</td>
                        </tr>
                    `);
                    $tbody.append(row);
                });
            }
            
            $container.show();
        },

        displayRagResults(data) {
            console.log('Displaying RAG results:', data);
            
            const $container = $('#rtbcb-rag-results');
            const $tbody = $('#rtbcb-rag-results-table tbody');
            
            $tbody.empty();
            
            if (data.results && Array.isArray(data.results)) {
                data.results.forEach(result => {
                    const score = parseFloat(result.score || 0);
                    const statusClass = score >= 0.8 ? 'status-good' : (score >= 0.5 ? 'status-warning' : 'status-error');
                    
                    const row = $(`
                        <tr class="${statusClass}">
                            <td>${this.escapeHtml(result.type || '--')}</td>
                            <td>${this.escapeHtml(result.ref_id || '--')}</td>
                            <td>${this.escapeHtml(result.metadata?.name || result.metadata?.title || '--')}</td>
                            <td>${score.toFixed(3)}</td>
                        </tr>
                    `);
                    $tbody.append(row);
                });
            }
            
            // Update metrics
            if (data.metrics) {
                $('#rtbcb-rag-metrics').text(
                    `Time: ${data.metrics.retrieval_time || 0}ms | Results: ${data.metrics.result_count || 0} | Avg Score: ${(data.metrics.average_score || 0).toFixed(3)}`
                );
            }
            
            $container.show();
            $('[data-action="copy-rag-context"], [data-action="export-rag-results"]').prop('disabled', false);
        },

        displayROIResults(data) {
            console.log('Displaying ROI results:', data);
            
            const $container = $('#roi-results-container');
            
            // Update ROI cards
            if (data.conservative) {
                $('#roi-conservative-percent').text(`${(data.conservative.roi_percentage || 0).toFixed(1)}%`);
                $('#roi-conservative-amount').text(`$${(data.conservative.total_annual_benefit || 0).toLocaleString()}`);
            }
            
            if (data.base) {
                $('#roi-realistic-percent').text(`${(data.base.roi_percentage || 0).toFixed(1)}%`);
                $('#roi-realistic-amount').text(`$${(data.base.total_annual_benefit || 0).toLocaleString()}`);
            }
            
            if (data.optimistic) {
                $('#roi-optimistic-percent').text(`${(data.optimistic.roi_percentage || 0).toFixed(1)}%`);
                $('#roi-optimistic-amount').text(`$${(data.optimistic.total_annual_benefit || 0).toLocaleString()}`);
            }
            
            $container.show();
            $('[data-action="export-roi-results"]').prop('disabled', false);
        },

        updateApiHealthResults(data) {
            console.log('Updating API health results:', data);
            
            $('#rtbcb-api-health-notice').text(
                data.overall_status === 'all_passed' ? 'All systems operational' : 'Some issues detected'
            );
            
            if (data.results) {
                Object.keys(data.results).forEach(component => {
                    const result = data.results[component];
                    const $row = $(`#rtbcb-api-${component}`);
                    const $indicator = $row.find('.rtbcb-status-indicator');
                    
                    $indicator.removeClass('status-good status-error')
                              .addClass(result.passed ? 'status-good' : 'status-error');
                    
                    $row.find('.rtbcb-last-tested').text(result.last_tested || '');
                    $row.find('.rtbcb-response-time').text(result.response_time ? `${result.response_time}ms` : '');
                    $row.find('.rtbcb-message').text(result.message || '');
                });
            }
        },

        // Utility methods
        clearResults() {
            $('#results-container, #error-container').hide();
            $('[data-action="export-results"], [data-action="clear-results"]').prop('disabled', true);
            this.showNotification('Results cleared', 'info');
        },

        showError(message) {
            const $container = $('#error-container');
            const $content = $('#error-content');
            
            $content.html(`<strong>Error:</strong> ${this.escapeHtml(message)}`);
            $container.show();
            $('#results-container').hide();
        },

        formatContent(content) {
            if (!content) return '';
            return content.replace(/\n\n/g, '</p><p>')
                          .replace(/\n/g, '<br>')
                          .replace(/^/, '<p>')
                          .replace(/$/, '</p>');
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        // Button state management
        setButtonState(selector, state, text) {
            const $button = $(selector);
            const defaultText = $button.data('default-text') || $button.text();

            $button.removeClass('rtbcb-loading rtbcb-success rtbcb-error');

            switch (state) {
                case 'loading':
                    $button.prop('disabled', true)
                           .addClass('rtbcb-loading')
                           .html(`<span class="dashicons dashicons-update rtbcb-spin"></span> ${text || 'Loading...'}`);
                    break;
                case 'success':
                    $button.prop('disabled', false)
                           .addClass('rtbcb-success')
                           .html(`<span class="dashicons dashicons-yes-alt"></span> ${text || 'Complete'}`);
                    setTimeout(() => $button.removeClass('rtbcb-success').html(this.escapeHtml(defaultText)), 3000);
                    break;
                case 'error':
                    $button.prop('disabled', false)
                           .addClass('rtbcb-error')
                           .html(`<span class="dashicons dashicons-warning"></span> ${text || 'Error'}`);
                    setTimeout(() => $button.removeClass('rtbcb-error').html(this.escapeHtml(defaultText)), 5000);
                    break;
                default:
                    $button.prop('disabled', false).html(text ? text : this.escapeHtml(defaultText));
                    break;
            }
        },

        // Progress management
        startProgress() {
            this.startTime = Date.now();
            $('#progress-container').show();
            $('#progress-fill').css('width', '0%');
            $('#progress-status').text('Starting...');
            
            let progress = 0;
            this.progressTimer = setInterval(() => {
                progress = Math.min(progress + Math.random() * 10, 90);
                $('#progress-fill').css('width', `${progress}%`);
                
                const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
                $('#progress-timer').text(`${Math.floor(elapsed / 60)}:${(elapsed % 60).toString().padStart(2, '0')}`);
            }, 500);
        },

        stopProgress() {
            if (this.progressTimer) {
                clearInterval(this.progressTimer);
                this.progressTimer = null;
            }
            $('#progress-fill').css('width', '100%');
            $('#progress-status').text('Complete!');
            
            setTimeout(() => {
                $('#progress-container').hide();
            }, 1000);
        },

        // AJAX request handling
        makeRequest(data) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: rtbcbDashboard.ajaxurl,
                    type: 'POST',
                    data: data,
                    timeout: 120000,
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data?.message || 'Request failed'));
                        }
                    },
                    error: (xhr, status, error) => {
                        let message = 'Request failed';
                        
                        if (status === 'timeout') {
                            message = 'Request timed out';
                        } else if (xhr.responseJSON && xhr.responseJSON.data) {
                            message = xhr.responseJSON.data.message || message;
                        } else if (error) {
                            message = error;
                        }
                        
                        reject(new Error(message));
                    }
                });
            });
        },

        // Chart management
        setupCharts() {
            // Initialize Chart.js defaults
            if (typeof Chart !== 'undefined') {
                Chart.defaults.responsive = true;
                Chart.defaults.maintainAspectRatio = false;
            }
        },

        createChart(chartId, config) {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not available');
                return null;
            }

            // Destroy existing chart
            if (this.charts[chartId]) {
                this.charts[chartId].destroy();
            }

            const ctx = document.getElementById(chartId);
            if (!ctx) {
                console.warn(`Chart canvas ${chartId} not found`);
                return null;
            }

            this.charts[chartId] = new Chart(ctx, config);
            return this.charts[chartId];
        },

        // Notifications
        showNotification(message, type = 'info') {
            const notification = $(`
                <div class="notice notice-${type} is-dismissible rtbcb-notification">
                    <p>${this.escapeHtml(message)}</p>
                </div>
            `);

            $('.wrap.rtbcb-unified-test-dashboard').prepend(notification);

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 5000);

            // Manual dismiss
            notification.find('.notice-dismiss').on('click', () => {
                notification.remove();
            });
        },

        updateApiHealthStatus() {
            // Update API health status if on that tab
            const lastTest = rtbcbDashboard.apiHealth?.lastResults;
            if (lastTest) {
                this.updateApiHealthResults(lastTest);
            }
        }
    };

    // Initialize when DOM is ready
    $(document).ready(() => {
        Dashboard.init();
    });

    // Expose for debugging
    window.RTBCBDashboard = Dashboard;

})(jQuery);

