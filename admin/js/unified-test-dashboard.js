/**
 * Fixed Unified Test Dashboard JavaScript
 * Handles all dashboard functionality with improved error handling and state management
 */
(function ensureDashboard() {
    const MAX_RETRIES = 5;
    const RETRY_DELAY = 50;
    let attempts = 0;

    function check() {
        if ( typeof rtbcbDashboard === 'undefined' ) {
            if ( attempts < MAX_RETRIES ) {
                attempts++;
                setTimeout( check, RETRY_DELAY );
            } else {
                console.error(`Failed to initialize dashboard after ${attempts} attempts (max ${MAX_RETRIES}): rtbcbDashboard is not defined`);
            }
            return;
        }

        (function($) {
        'use strict';

        if (typeof jQuery === 'undefined') {
            console.error('jQuery is not available');
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
                // Clean up any existing event handlers first
                $(document).off('.rtbcb-dashboard');
                $(document).off('.rtbcb-dashboard-backup');

                // Reset any stuck button states first
                this.resetAllButtonStates();

                this.bindEvents();
                this.initializeTabs();
                this.setupValidation();
                this.loadSavedState();

                if (!rtbcbDashboard.nonces || !rtbcbDashboard.nonces.apiHealth) {
                    this.showNotification('Security token missing. Please refresh the page.', 'warning');
                }

                // Initialize Chart.js if available
                if (typeof Chart !== 'undefined') {
                    this.setupCharts();
                }

                // Add emergency reset handler (Ctrl/Cmd + R while on dashboard)
                $(document).on('keydown.rtbcb-dashboard', (e) => {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                        this.resetAllButtonStates();
                    }
                });

                // Add a backup click handler for critical buttons in case the main system fails
                $(document).on('click.rtbcb-dashboard-backup', 'button[data-action]', (e) => {
                    const $button = $(e.currentTarget);

                    // Only handle if the button appears to be stuck or unresponsive
                    const isLoading = $button.hasClass('rtbcb-loading');
                    const isDisabled = $button.is(':disabled');
                    const isGenerating = this.isGenerating;
                    const lastActivated = parseInt($button.attr('data-last-activated') || '0', 10);
                    const now = Date.now();
                    const MIN_STUCK_TIME = 5000; // 5 seconds

                    // Consider button stuck if not loading, not generating, not disabled, and last activation was >5s ago
                    if (!isLoading && !isGenerating && !isDisabled && (now - lastActivated > MIN_STUCK_TIME)) {
                        console.log('Backup click handler triggered for:', $button.data('action'));

                        // Small delay to allow main handler to work first
                        setTimeout(() => {
                            if (!this.isGenerating) {
                                const action = $button.data('action');
                                this.handleBackupAction(action, $button);
                            }
                        }, 50);
                    }
                });

                console.log('Dashboard initialized successfully');
            } catch (error) {
                console.error('Dashboard initialization failed:', error);
                this.showNotification('Dashboard initialization failed. Please refresh the page.', 'error');
            }
        },

        // Backup action handler for when main system fails
        handleBackupAction(action, $button) {
            console.log('Executing backup action:', action);

            switch (action) {
                case 'toggle-api-key':
                    this.toggleApiKeyVisibility();
                    break;
                case 'run-company-overview':
                    this.generateCompanyOverview();
                    break;
                case 'clear-results':
                    this.clearResults();
                    break;
                case 'run-llm-test':
                    this.runLLMTest();
                    break;
                case 'run-rag-test':
                    this.runRagTest();
                    break;
                case 'rebuild-rag-index':
                    this.rebuildRagIndex();
                    break;
                case 'api-health-ping':
                    this.runAllApiTests();
                    break;
                case 'calculate-roi':
                    this.calculateROI();
                    break;
                default:
                    console.log('No backup handler for action:', action);
            }
        },

        // Bind all event handlers
        bindEvents() {
            console.log('Binding events...');
            
            // Remove any existing handlers to prevent duplicates
            $(document).off('.rtbcb-dashboard');
            
            // Store reference to Dashboard object for event handlers
            const self = this;
            
            // Enhanced event binding for cross-platform compatibility
            this.bindCrossPlatformEvents();
            
            console.log('Events bound successfully');
        },

        // Simplified cross-platform event binding
        bindCrossPlatformEvents() {
            const self = this;

            // Remove existing handlers first
            $(document).off('.rtbcb-dashboard');

            // Simple click handlers - one for each action
            $(document).on('click.rtbcb-dashboard', '[data-action="run-company-overview"]', function(e) {
                e.preventDefault();
                if (!$(this).prop('disabled') && !self.isGenerating) {
                    self.generateCompanyOverview();
                }
            });

            $(document).on('click.rtbcb-dashboard', '[data-action="clear-results"]', function(e) {
                e.preventDefault();
                self.clearResults();
            });

            $(document).on('click.rtbcb-dashboard', '[data-action="run-llm-test"]', function(e) {
                e.preventDefault();
                if (!$(this).prop('disabled') && !self.isGenerating) {
                    self.runLLMTest();
                }
            });

            $(document).on('click.rtbcb-dashboard', '[data-action="run-rag-test"]', function(e) {
                e.preventDefault();
                if (!$(this).prop('disabled') && !self.isGenerating) {
                    self.runRagTest();
                }
            });

            $(document).on('click.rtbcb-dashboard', '[data-action="rebuild-rag-index"]', function(e) {
                e.preventDefault();
                if (!$(this).prop('disabled') && !self.isGenerating) {
                    self.rebuildRagIndex();
                }
            });

            $(document).on('click.rtbcb-dashboard', '[data-action="api-health-ping"]', function(e) {
                e.preventDefault();
                if (!$(this).prop('disabled') && !self.isGenerating) {
                    self.runAllApiTests();
                }
            });

            $(document).on('click.rtbcb-dashboard', '[data-action="calculate-roi"]', function(e) {
                e.preventDefault();
                if (!$(this).prop('disabled') && !self.isGenerating) {
                    self.calculateROI();
                }
            });

            $(document).on('click.rtbcb-dashboard', '[data-action="toggle-api-key"]', function(e) {
                e.preventDefault();
                self.toggleApiKeyVisibility();
            });

            // Tab navigation
            $(document).on('click.rtbcb-dashboard', '.rtbcb-test-tabs .nav-tab', function(e) {
                e.preventDefault();
                const tab = $(this).data('tab');
                if (tab && !self.isGenerating) {
                    self.switchTab(tab);
                }
            });

            // Form submission
            $(document).on('submit.rtbcb-dashboard', '#rtbcb-dashboard-settings-form', function(e) {
                e.preventDefault();
                self.saveSettings();
            });

            // Input handlers (keep these as they were working)
            $(document).on('input.rtbcb-dashboard', '#company-name-input', function() {
                clearTimeout(self.inputTimer);
                self.inputTimer = setTimeout(() => self.validateCompanyInput(), 300);
            });

            $(document).on('input.rtbcb-dashboard', '#llm-temperature', function(e) {
                $('#llm-temperature-value').text($(e.target).val());
            });

            $(document).on('change.rtbcb-dashboard', 'input[name="test-models[]"]', function() {
                self.validateLLMInputs();
            });

            $(document).on('input.rtbcb-dashboard', '#rtbcb-rag-query', function() {
                clearTimeout(self.ragTimer);
                self.ragTimer = setTimeout(() => self.validateRagQuery(), 300);
            });
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
        // Enhanced setup form validation with API connection testing
        setupValidation() {
            console.log('Setting up validation...');
            this.validateCompanyInput();
            this.validateLLMInputs();
            this.validateRagQuery();
            
            // Add initial connection test if API key is configured
            if (rtbcbDashboard.apiKeyConfigured) {
                this.testInitialConnection();
            }
        },

        // Test initial API connection
        testInitialConnection() {
            console.log('Testing initial API connection...');
            
            // Simple ping test without updating UI heavily
            const testData = {
                action: 'rtbcb_test_api_connection',
                nonce: rtbcbDashboard.nonces?.apiHealth || rtbcbDashboard.nonces?.dashboard || ''
            };
            
            this.makeRequest(testData)
                .then(response => {
                    console.log('Initial API connection test passed:', response);
                    this.updateConnectionStatus('connected');
                })
                .catch(error => {
                    console.warn('Initial API connection test failed:', error.message);
                    this.updateConnectionStatus('error', error.message);
                });
        },

        // Update connection status indicator
        updateConnectionStatus(status, message = '') {
            const $indicators = $('.rtbcb-system-status-bar .rtbcb-status-indicator');
            const $apiIndicator = $indicators.filter(':contains("OpenAI API")');
            
            if ($apiIndicator.length) {
                const $icon = $apiIndicator.find('.dashicons');
                
                switch (status) {
                    case 'connected':
                        $apiIndicator.removeClass('status-error status-warning').addClass('status-good');
                        $icon.removeClass('dashicons-warning dashicons-info').addClass('dashicons-yes-alt');
                        break;
                    case 'error':
                        $apiIndicator.removeClass('status-good status-warning').addClass('status-error');
                        $icon.removeClass('dashicons-yes-alt dashicons-info').addClass('dashicons-warning');
                        if (message) {
                            $apiIndicator.attr('title', message);
                        }
                        break;
                    case 'warning':
                        $apiIndicator.removeClass('status-good status-error').addClass('status-warning');
                        $icon.removeClass('dashicons-yes-alt dashicons-warning').addClass('dashicons-info');
                        break;
                }
            }
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
                    this.showError(error.message || 'Failed to generate overview', {
                        action: 'generateCompanyOverview',
                        companyName: companyName,
                        model: $('#model-selection').val(),
                        timestamp: new Date().toISOString(),
                        error: error
                    });
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

            if (!circuitBreaker.canExecute()) {
                this.showNotification('Too many failures. Please wait before trying again.', 'warning');
                return;
            }

            console.log('Running LLM test...', { prompt, models: selectedModels });

            this.isGenerating = true;
            this.setButtonState('[data-action="run-llm-test"]', 'loading');
            this.startProgress();

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
                    circuitBreaker.recordSuccess();
                    this.displayLLMResults(response);
                    this.setButtonState('[data-action="run-llm-test"]', 'success');
                })
                .catch(error => {
                    circuitBreaker.recordFailure();
                    console.error('LLM test error:', error);
                    this.showError(error.message || 'LLM test failed');
                    this.setButtonState('[data-action="run-llm-test"]', 'error');
                })
                .finally(() => {
                    this.isGenerating = false;
                    this.stopProgress();
                });
        },

        // RAG System functionality
        runRagTest() {
            if (this.isGenerating) return;

            const query = $('#rtbcb-rag-query').val().trim();
            if (!query) {
                this.showNotification('Please enter a query', 'error');
                return;
            }

            if (!circuitBreaker.canExecute()) {
                this.showNotification('Too many failures. Please wait before trying again.', 'warning');
                return;
            }

            console.log('Running RAG test...', { query });

            this.isGenerating = true;
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
                    circuitBreaker.recordSuccess();
                    this.displayRagResults(response);
                    this.setButtonState('[data-action="run-rag-test"]', 'success');
                })
                .catch(error => {
                    circuitBreaker.recordFailure();
                    console.error('RAG test error:', error);
                    this.showError(error.message || 'RAG test failed');
                    this.setButtonState('[data-action="run-rag-test"]', 'error');
                })
                .finally(() => {
                    this.isGenerating = false;
                });
        },

        rebuildRagIndex() {
            if (this.isGenerating) return;

            if (!circuitBreaker.canExecute()) {
                this.showNotification('Too many failures. Please wait before trying again.', 'warning');
                return;
            }

            console.log('Rebuilding RAG index...');

            this.isGenerating = true;
            this.setButtonState('[data-action="rebuild-rag-index"]', 'loading');

            const requestData = {
                action: 'rtbcb_rag_rebuild_index',
                nonce: rtbcbDashboard.nonces?.dashboard || ''
            };

            this.makeRequest(requestData)
                .then(response => {
                    circuitBreaker.recordSuccess();
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
                    circuitBreaker.recordFailure();
                    console.error('RAG rebuild error:', error);
                    this.showError(error.message || 'RAG index rebuild failed');
                    this.setButtonState('[data-action="rebuild-rag-index"]', 'error');
                })
                .finally(() => {
                    this.isGenerating = false;
                });
        },

        // API Health functionality
        runAllApiTests() {
            if (this.isGenerating) return;

            if (!circuitBreaker.canExecute()) {
                this.showNotification('Too many failures. Please wait before trying again.', 'warning');
                return;
            }

            if (!rtbcbDashboard.nonces || !rtbcbDashboard.nonces.apiHealth) {
                this.showNotification('Missing security token. Please refresh the page and try again.', 'error');
                return;
            }

            console.log('Running API health tests...');

            this.isGenerating = true;
            this.setButtonState('[data-action="api-health-ping"]', 'loading');
            $('#rtbcb-api-health-notice').text('Running comprehensive API tests...');

            const requestData = {
                action: 'rtbcb_run_api_health_tests',
                nonce: rtbcbDashboard.nonces?.apiHealth
            };

            this.makeRequest(requestData)
                .then(response => {
                    circuitBreaker.recordSuccess();
                    this.updateApiHealthResults(response);
                    this.setButtonState('[data-action="api-health-ping"]', 'success');
                    this.showNotification('API health tests completed', 'success');
                })
                .catch(error => {
                    circuitBreaker.recordFailure();
                    console.error('API health test error:', error);
                    this.showError(error.message || 'API health tests failed', {
                        action: 'runAllApiTests',
                        timestamp: new Date().toISOString(),
                        error: error
                    });
                    this.setButtonState('[data-action="api-health-ping"]', 'error');
                    $('#rtbcb-api-health-notice').text('API tests failed');
                })
                .finally(() => {
                    this.isGenerating = false;
                });
        },

        // ROI Calculator functionality
        calculateROI() {
            if (this.isGenerating) return;

            console.log('Calculating ROI...');

            this.isGenerating = true;
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
                })
                .finally(() => {
                    this.isGenerating = false;
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
            
            const $notice = $('#rtbcb-api-health-notice');
            
            if (data.overall_status === 'all_passed') {
                $notice.text('All systems operational').removeClass('error').addClass('success');
            } else {
                $notice.text('Some issues detected').removeClass('success').addClass('error');
            }
            
            if (data.results && typeof data.results === 'object') {
                Object.keys(data.results).forEach(component => {
                    const result = data.results[component];
                    const $row = $(`#rtbcb-api-${component}`);
                    
                    if ($row.length) {
                        const $indicator = $row.find('.rtbcb-status-indicator');
                        
                        // Update status indicator
                        $indicator.removeClass('status-good status-error status-warning')
                                  .addClass(result.passed ? 'status-good' : 'status-error');
                        
                        // Update individual fields with fallbacks
                        const $lastTested = $row.find('.rtbcb-last-tested');
                        if ($lastTested.length) {
                            $lastTested.text(result.last_tested || new Date().toLocaleTimeString());
                        }
                        
                        const $responseTime = $row.find('.rtbcb-response-time');
                        if ($responseTime.length && result.response_time) {
                            $responseTime.text(`${result.response_time}ms`);
                        }
                        
                        const $message = $row.find('.rtbcb-message');
                        if ($message.length) {
                            $message.text(result.message || (result.passed ? 'OK' : 'Failed'));
                        }
                    }
                });
            }
            
            // Update last check timestamp
            const $lastCheck = $('#rtbcb-api-health-last-check');
            if ($lastCheck.length) {
                $lastCheck.text(`Last checked: ${new Date().toLocaleString()}`);
            }
        },

        // Utility methods
        clearResults() {
            $('#results-container, #error-container').hide();
            $('[data-action="export-results"], [data-action="clear-results"]').prop('disabled', true);
            this.showNotification('Results cleared', 'info');
        },

        // Enhanced error display with debugging options
        showError(message, debugInfo = null) {
            // Dismiss any existing notifications first
            this.dismissNotifications();
            
            const $container = $('#error-container');
            const $content = $('#error-content');
            
            let errorHtml = `<strong>Error:</strong> ${this.escapeHtml(message)}`;
            
            // Add debug information if available and debug mode is enabled
            if (debugInfo && ($('#show-debug-info').is(':checked') || this.debugMode)) {
                errorHtml += `
                    <div class="rtbcb-debug-error" style="margin-top: 10px; padding: 10px; background: #fafafa; border: 1px solid #ddd; border-radius: 4px;">
                        <strong>Debug Information:</strong>
                        <details style="margin-top: 5px;">
                            <summary style="cursor: pointer; font-weight: bold;">Click to expand</summary>
                            <pre style="margin-top: 5px; font-size: 12px; white-space: pre-wrap;">${this.escapeHtml(JSON.stringify(debugInfo, null, 2))}</pre>
                        </details>
                    </div>
                `;
            }
            
            // Add troubleshooting tips for common errors
            if (message.includes('timeout') || message.includes('timed out')) {
                errorHtml += `
                    <div class="rtbcb-error-tips" style="margin-top: 10px; padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                        <strong>💡 Troubleshooting tip:</strong> Request timed out. Try refreshing the page or check your internet connection.
                    </div>
                `;
            } else if (message.includes('Permission denied') || message.includes('403')) {
                errorHtml += `
                    <div class="rtbcb-error-tips" style="margin-top: 10px; padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                        <strong>💡 Troubleshooting tip:</strong> Permission denied. Please refresh the page and try again.
                    </div>
                `;
            } else if (message.includes('API') || message.includes('Network error')) {
                errorHtml += `
                    <div class="rtbcb-error-tips" style="margin-top: 10px; padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                        <strong>💡 Troubleshooting tip:</strong> API connection issue. Check your API key configuration in Settings.
                    </div>
                `;
            }
            
            $content.html(errorHtml);
            $container.show();
            $('#results-container').hide();
            
            // Add a manual retry button for failed operations
            if (!$container.find('.rtbcb-retry-button').length) {
                const retryButton = $(`
                    <button type="button" class="button button-small rtbcb-retry-button" style="margin-top: 10px;">
                        <span class="dashicons dashicons-update"></span> Retry
                    </button>
                `);
                
                retryButton.on('click', () => {
                    $container.fadeOut();
                    this.resetAllButtonStates();
                });
                
                $content.append(retryButton);
            }
            
            // Auto-dismiss error after 15 seconds (increased from 10)
            setTimeout(() => {
                $container.fadeOut();
            }, 15000);
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

        // Enhanced button state management
        setButtonState(selector, state, text) {
            const $button = $(selector);
            if ($button.length === 0) {
                console.warn('Button not found:', selector);
                return;
            }
            
            // Store original text if not already stored
            if (!$button.data('default-text')) {
                $button.data('default-text', $button.text().trim());
            }
            const defaultText = $button.data('default-text');

            // Clear all state classes first
            $button.removeClass('rtbcb-loading rtbcb-success rtbcb-error rtbcb-touch-active');

            switch (state) {
                case 'loading':
                    $button.prop('disabled', true)
                           .addClass('rtbcb-loading')
                           .css('pointer-events', 'none') // Explicitly disable pointer events
                           .html(`<span class="dashicons dashicons-update rtbcb-spin"></span> ${text || 'Loading...'}`);
                    break;
                case 'success':
                    $button.prop('disabled', false)
                           .addClass('rtbcb-success')
                           .css('pointer-events', 'auto') // Explicitly enable pointer events
                           .html(`<span class="dashicons dashicons-yes-alt"></span> ${text || 'Complete'}`);
                    // Reset after delay
                    setTimeout(() => {
                        if ($button.hasClass('rtbcb-success')) { // Only reset if still in success state
                            this.resetButtonState($button, defaultText);
                        }
                    }, 3000);
                    break;
                case 'error':
                    $button.prop('disabled', false)
                           .addClass('rtbcb-error')
                           .css('pointer-events', 'auto') // Explicitly enable pointer events
                           .html(`<span class="dashicons dashicons-warning"></span> ${text || 'Error'}`);
                    // Reset after delay
                    setTimeout(() => {
                        if ($button.hasClass('rtbcb-error')) { // Only reset if still in error state
                            this.resetButtonState($button, defaultText);
                        }
                    }, 5000);
                    break;
                case 'reset':
                case 'default':
                default:
                    this.resetButtonState($button, text || defaultText);
                    break;
            }
        },

        // Reset button to default state
        resetButtonState($button, text) {
            $button.prop('disabled', false)
                   .removeClass('rtbcb-loading rtbcb-success rtbcb-error rtbcb-touch-active')
                   .css('pointer-events', 'auto')
                   .html(this.escapeHtml(text));
        },

        // Force reset all buttons (emergency cleanup)
        resetAllButtonStates() {
            console.log('Resetting all button states...');

            $('button[data-action]').each((index, element) => {
                const $button = $(element);

                // Clear all interaction tracking data
                $button.removeData('rtbcb-interacted');
                $button.removeData('rtbcb-interacted-time');

                const defaultText = $button.data('default-text') || $button.text().trim();
                this.resetButtonState($button, defaultText);
            });

            // Also reset any other interactive elements (excluding buttons already handled above)
            $('[data-action]:not(button)').each((index, element) => {
                const $element = $(element);
                $element.removeData('rtbcb-interacted');
                $element.removeData('rtbcb-interacted-time');
            });

            this.isGenerating = false;

            // Force remove any stuck loading states
            $('.rtbcb-loading').removeClass('rtbcb-loading');
            $('.rtbcb-touch-active').removeClass('rtbcb-touch-active');
        },

        // Progress management
        startProgress() {
            this.clearProgress();
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
            this.clearProgress();
            $('#progress-fill').css('width', '100%');
            $('#progress-status').text('Complete!');
            
            setTimeout(() => {
                $('#progress-container').hide();
                $('#progress-fill').css('width', '0%');
                $('#progress-status').text('');
                $('#progress-timer').text('0:00');
            }, 1000);
        },

        clearProgress() {
            if (this.progressTimer) {
                clearInterval(this.progressTimer);
                this.progressTimer = null;
            }
        },

        // Enhanced AJAX request handling with improved error reporting
        makeRequest(data) {
            // Abort any existing request
            if (this.currentRequest) {
                this.currentRequest.abort();
                this.currentRequest = null;
            }
            
            console.log('Making API request:', data.action, data);
            
            return new Promise((resolve, reject) => {
                this.currentRequest = $.ajax({
                    url: rtbcbDashboard.ajaxurl,
                    type: 'POST',
                    data: data,
                    timeout: 120000,
                    beforeSend: (xhr) => {
                        console.log('API request started:', data.action);
                    },
                    success: (response, textStatus, xhr) => {
                        this.currentRequest = null;
                        console.log('API response received:', {
                            action: data.action,
                            success: response.success,
                            status: xhr.status,
                            response: response
                        });
                        
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            const errorMessage = this.extractErrorMessage(response);
                            console.error('API request failed:', {
                                action: data.action,
                                error: errorMessage,
                                response: response
                            });
                            reject(new Error(errorMessage));
                        }
                    },
                    error: (xhr, status, error) => {
                        this.currentRequest = null;
                        
                        // Don't reject if request was aborted
                        if (status === 'abort') {
                            console.log('Request aborted');
                            return;
                        }
                        
                        const errorDetails = {
                            action: data.action,
                            status: status,
                            httpStatus: xhr.status,
                            error: error,
                            responseText: xhr.responseText
                        };
                        
                        console.error('API request error:', errorDetails);
                        
                        let message = this.getErrorMessage(xhr, status, error);
                        
                        // Add debugging information in development
                        if (typeof DEBUG !== 'undefined' && DEBUG) {
                            if (console.groupCollapsed) {
                                console.groupCollapsed('API Error Details');
                                console.log('Status:', status);
                                console.log('HTTP Status:', xhr.status);
                                console.log('Error:', error);
                                console.log('Response:', xhr.responseText);
                                console.log('Full XHR:', xhr);
                                console.groupEnd();
                            } else {
                                console.log('API Error Details:', {
                                    status: status,
                                    httpStatus: xhr.status,
                                    error: error,
                                    response: xhr.responseText,
                                    xhr: xhr
                                });
                            }
                        }
                        
                        reject(new Error(message));
                    }
                });
            });
        },

        // Extract error message from API response
        extractErrorMessage(response) {
            // Try multiple paths to find error message
            if (response.data && typeof response.data === 'string') {
                return response.data;
            }
            if (response.data && response.data.message) {
                return response.data.message;
            }
            if (response.data && response.data.error) {
                return response.data.error;
            }
            if (response.message) {
                return response.message;
            }
            return 'API request failed - no error message provided';
        },

        // Get user-friendly error message
        getErrorMessage(xhr, status, error) {
            if (status === 'timeout') {
                return 'Request timed out. Please check your connection and try again.';
            }
            
            if (xhr.status === 0) {
                return 'Network error. Please check your internet connection.';
            }
            
            if (xhr.status === 403) {
                return 'Permission denied. Please refresh the page and try again.';
            }
            
            if (xhr.status === 404) {
                return 'API endpoint not found. Please contact support.';
            }
            
            if (xhr.status === 500) {
                return 'Server error. Please try again later.';
            }
            
            if (xhr.status === 502 || xhr.status === 503 || xhr.status === 504) {
                return 'Service temporarily unavailable. Please try again later.';
            }
            
            // Try to parse JSON error response
            try {
                const jsonResponse = JSON.parse(xhr.responseText);
                if (jsonResponse.data && jsonResponse.data.message) {
                    return jsonResponse.data.message;
                }
                if (jsonResponse.message) {
                    return jsonResponse.message;
                }
            } catch (e) {
                // Not JSON or malformed
            }
            
            // Default fallback
            return `Request failed: ${error || 'Unknown error'} (HTTP ${xhr.status})`;
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
            // Dismiss existing notifications first
            this.dismissNotifications();
            
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

        dismissNotifications() {
            $('.rtbcb-notification').fadeOut(() => {
                $('.rtbcb-notification').remove();
            });
        },

        updateApiHealthStatus() {
            // Update API health status if on that tab
            const lastTest = rtbcbDashboard.apiHealth?.lastResults;
            if (lastTest) {
                this.updateApiHealthResults(lastTest);
            }
        },

        // Cleanup method
        cleanup() {
            // Abort any pending request
            if (this.currentRequest) {
                this.currentRequest.abort();
                this.currentRequest = null;
            }
            
            // Clear progress timer
            this.clearProgress();
            
            // Clear any timeouts
            if (this.progressTimer) {
                clearInterval(this.progressTimer);
                this.progressTimer = null;
            }
            
            // Reset all button states
            this.resetAllButtonStates();
            
            // Remove event handlers
            $(document).off('.rtbcb-dashboard');
            
            // Dismiss notifications
            this.dismissNotifications();
            
            console.log('Dashboard cleanup completed');
        }
    };

    // Initialize when DOM is ready
    $(document).ready(() => {
        Dashboard.init();
    });

    // Cleanup on page unload
    $(window).on('beforeunload', () => {
        Dashboard.cleanup();
    });

    // Expose for debugging
    window.RTBCBDashboard = Dashboard;

    })(jQuery);
}

// Start initialization: wait for rtbcbDashboard to be available, then initialize dashboard logic
waitForDashboardAndInit();
})();

