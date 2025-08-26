/**
 * Unified Test Dashboard JavaScript - Fixed Version
 * Handles all dashboard functionality with proper jQuery noConflict support
 * 
 * Fixes:
 * - Proper jQuery noConflict wrapper
 * - Consistent event delegation
 * - Improved error handling
 * - Better script loading order handling
 */

// Main dashboard initialization - properly wrapped for WordPress jQuery noConflict
(function($) {
    'use strict';

    // Early exit if jQuery is not available
    if (typeof $ === 'undefined' || typeof jQuery === 'undefined') {
        console.error('RTBCB Dashboard: jQuery is not available');
        return;
    }

    // Debug indicator that this script is loading
    console.log('RTBCB Dashboard: Script loading with jQuery', $.fn.jquery);

    // Wait for rtbcbDashboard to be available
    function waitForDashboardConfig() {
        if (typeof rtbcbDashboard === 'undefined') {
            console.log('RTBCB Dashboard: Waiting for rtbcbDashboard config...');
            setTimeout(waitForDashboardConfig, 100);
            return;
        }
        
        console.log('RTBCB Dashboard: Config available, initializing...');
        initializeDashboard();
    }

    // Initialize when document is ready
    $(document).ready(function() {
        // Visual indicator that jQuery is working
        $('body').css('border-top', '3px solid green');
        
        waitForDashboardConfig();
    });

    function initializeDashboard() {
        // Utility functions
        var debounce = function(func, delay) {
            var timeoutId;
            return function() {
                var context = this;
                var args = arguments;
                clearTimeout(timeoutId);
                timeoutId = setTimeout(function() {
                    func.apply(context, args);
                }, delay);
            };
        };

        // Circuit breaker for API failures
        var circuitBreaker = {
            failures: 0,
            threshold: parseInt((rtbcbDashboard.circuitBreaker && rtbcbDashboard.circuitBreaker.threshold) || 5, 10),
            resetTime: parseInt((rtbcbDashboard.circuitBreaker && rtbcbDashboard.circuitBreaker.resetTime) || 60000, 10),
            lastFailTime: 0,

            canExecute: function() {
                if (this.failures < this.threshold) return true;
                
                var now = Date.now();
                if (now - this.lastFailTime > this.resetTime) {
                    this.reset();
                    return true;
                }
                return false;
            },

            recordFailure: function() {
                this.failures++;
                this.lastFailTime = Date.now();
                console.warn('[Circuit Breaker] Failure ' + this.failures + '/' + this.threshold);
            },

            recordSuccess: function() {
                this.failures = 0;
            },

            reset: function() {
                this.failures = 0;
                console.log('[Circuit Breaker] Reset');
            }
        };

        // Main Dashboard object
        var Dashboard = {
            currentTab: 'company-overview',
            isGenerating: false,
            progressTimer: null,
            startTime: null,
            currentRequest: null,
            charts: {},

            // Initialize dashboard
            init: function() {
                console.log('Dashboard initializing...');

                try {
                    // Clean up any existing event handlers first
                    $(document).off('.rtbcb-dashboard');

                    // Reset any stuck button states first
                    this.resetAllButtonStates();

                    this.bindEvents();
                    this.initializeTabs();
                    this.setupValidation();
                    this.loadSavedState();

                    // Show startup notification
                    this.showNotification('Dashboard loaded successfully', 'success');

                    console.log('Dashboard initialized successfully');
                } catch (error) {
                    console.error('Dashboard initialization failed:', error);
                    this.showNotification('Dashboard initialization failed. Please refresh the page.', 'error');
                }
            },

            // Bind all event handlers with proper delegation
            bindEvents: function() {
                console.log('Binding events...');
                var self = this;

                // Remove any existing handlers to prevent duplicates
                $(document).off('.rtbcb-dashboard');

                // Button click handlers using event delegation
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
                    var tab = $(this).data('tab');
                    if (tab && !self.isGenerating) {
                        self.switchTab(tab);
                    }
                });

                // Form submission
                $(document).on('submit.rtbcb-dashboard', '#rtbcb-dashboard-settings-form', function(e) {
                    e.preventDefault();
                    self.saveSettings();
                });

                // Input handlers
                $(document).on('input.rtbcb-dashboard', '#company-name-input', debounce(function() {
                    localStorage.setItem('rtbcb_company_name', $(this).val());
                }, 500));

                // Visual feedback for all data-action buttons
                $(document).on('click.rtbcb-dashboard', '[data-action]', function() {
                    var $button = $(this);
                    $button.css('background-color', '#ffeb3b');
                    setTimeout(function() {
                        $button.css('background-color', '');
                    }, 200);
                    console.log('Button clicked:', $button.data('action'));
                });

                console.log('Events bound successfully');
            },

            // Initialize tabs
            initializeTabs: function() {
                // Set active tab
                var hash = window.location.hash.substring(1);
                if (hash && $('#' + hash).length) {
                    this.currentTab = hash;
                } else {
                    this.currentTab = 'company-overview';
                }
                this.switchTab(this.currentTab, false);
            },

            // Switch tabs
            switchTab: function(tabId, updateUrl) {
                if (updateUrl !== false) {
                    updateUrl = true;
                }

                // Update tab navigation
                $('.rtbcb-test-tabs .nav-tab').removeClass('nav-tab-active');
                $('.rtbcb-test-tabs .nav-tab[data-tab="' + tabId + '"]').addClass('nav-tab-active');

                // Update content sections
                $('.rtbcb-test-section').hide();
                $('#' + tabId).show();

                this.currentTab = tabId;

                if (updateUrl) {
                    window.location.hash = tabId;
                }

                console.log('Switched to tab:', tabId);
            },

            // Setup form validation
            setupValidation: function() {
                // Add real-time API key validation
                var self = this;
                $('#rtbcb_openai_api_key').on('input', debounce(function() {
                    var key = $(this).val().trim();
                    if (key.length > 10) {
                        self.validateApiKey(key);
                    }
                }, 1000));
            },

            // Load saved state
            loadSavedState: function() {
                // Restore company name
                var savedCompanyName = localStorage.getItem('rtbcb_company_name');
                if (savedCompanyName) {
                    $('#company-name-input').val(savedCompanyName);
                }

                // Update API key status
                this.updateApiKeyStatus(rtbcbDashboard.api_valid || false);
            },

            // Reset all button states
            resetAllButtonStates: function() {
                $('[data-action]').prop('disabled', false).removeClass('rtbcb-touch-active loading');
                $('.rtbcb-loading').removeClass('rtbcb-loading');
            },

            // Set button state
            setButtonState: function(selector, state) {
                var $button = $(selector);
                switch (state) {
                    case 'loading':
                        $button.prop('disabled', true).addClass('loading');
                        break;
                    case 'disabled':
                        $button.prop('disabled', true);
                        break;
                    case 'enabled':
                        $button.prop('disabled', false).removeClass('loading');
                        break;
                }
            },

            // Show notification
            showNotification: function(message, type) {
                type = type || 'info';
                
                // Remove existing notifications
                $('.rtbcb-notification').remove();
                
                var $notification = $('<div class="rtbcb-notification rtbcb-notification-' + type + '">')
                    .text(message)
                    .css({
                        position: 'fixed',
                        top: '32px',
                        right: '20px',
                        padding: '12px 20px',
                        borderRadius: '4px',
                        fontWeight: '500',
                        zIndex: '9999',
                        boxShadow: '0 2px 8px rgba(0,0,0,0.15)'
                    });

                // Set colors based on type
                switch (type) {
                    case 'success':
                        $notification.css({ backgroundColor: '#d4edda', color: '#155724', borderLeft: '4px solid #28a745' });
                        break;
                    case 'error':
                        $notification.css({ backgroundColor: '#f8d7da', color: '#721c24', borderLeft: '4px solid #dc3545' });
                        break;
                    case 'warning':
                        $notification.css({ backgroundColor: '#fff3cd', color: '#856404', borderLeft: '4px solid #ffc107' });
                        break;
                    default:
                        $notification.css({ backgroundColor: '#d1ecf1', color: '#0c5460', borderLeft: '4px solid #17a2b8' });
                }

                $('body').append($notification);

                // Auto-remove after 5 seconds
                setTimeout(function() {
                    $notification.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);

                console.log('Notification:', type, message);
            },

            // Toggle API key visibility
            toggleApiKeyVisibility: function() {
                var $input = $('#rtbcb_openai_api_key');
                var $button = $('[data-action="toggle-api-key"]');
                
                if ($input.attr('type') === 'password') {
                    $input.attr('type', 'text');
                    $button.text('Hide');
                } else {
                    $input.attr('type', 'password');
                    $button.text('Show');
                }
            },

            // Update API key status
            updateApiKeyStatus: function(isValid) {
                var $indicator = $('.api-key-status');
                var $buttons = $('[data-action="run-company-overview"], [data-action="run-llm-test"], [data-action="api-health-ping"]');
                
                if (isValid) {
                    $indicator.removeClass('invalid').addClass('valid').text('✓ Valid');
                    $buttons.prop('disabled', false);
                } else {
                    $indicator.removeClass('valid').addClass('invalid').text('✗ Invalid');
                    $buttons.prop('disabled', true);
                }
            },

            // Clear results
            clearResults: function() {
                $('#results-container, #error-container').hide();
                $('[data-action="export-results"], [data-action="clear-results"]').prop('disabled', true);
                this.showNotification('Results cleared', 'info');
            },

            // Make AJAX request with proper error handling
            makeRequest: function(data) {
                var self = this;
                
                return $.ajax({
                    url: rtbcbDashboard.ajaxurl,
                    type: 'POST',
                    data: data,
                    timeout: 120000, // 2 minutes
                    beforeSend: function() {
                        console.log('Making request:', data.action);
                    }
                }).done(function(response) {
                    console.log('Request successful:', data.action, response);
                    circuitBreaker.recordSuccess();
                    return response;
                }).fail(function(xhr, status, error) {
                    console.error('Request failed:', data.action, status, error);
                    circuitBreaker.recordFailure();
                    
                    var errorMessage = 'Request failed: ' + (error || 'Unknown error');
                    if (xhr.status) {
                        errorMessage += ' (HTTP ' + xhr.status + ')';
                    }
                    
                    self.showNotification(errorMessage, 'error');
                    throw new Error(errorMessage);
                });
            },

            // Placeholder methods for dashboard functionality
            generateCompanyOverview: function() {
                var companyName = $('#company-name-input').val().trim();
                if (!companyName) {
                    this.showNotification('Please enter a company name', 'error');
                    return;
                }

                if (!circuitBreaker.canExecute()) {
                    this.showNotification('Too many failures. Please wait before trying again.', 'warning');
                    return;
                }

                console.log('Generating overview for:', companyName);
                this.showNotification('Generating company overview...', 'info');
                
                // Implementation would go here
            },

            runLLMTest: function() {
                if (!circuitBreaker.canExecute()) {
                    this.showNotification('Too many failures. Please wait before trying again.', 'warning');
                    return;
                }

                console.log('Running LLM test...');
                this.showNotification('Running LLM test...', 'info');
                
                // Implementation would go here
            },

            runRagTest: function() {
                console.log('Running RAG test...');
                this.showNotification('Running RAG test...', 'info');
                
                // Implementation would go here
            },

            rebuildRagIndex: function() {
                console.log('Rebuilding RAG index...');
                this.showNotification('Rebuilding RAG index...', 'info');
                
                // Implementation would go here
            },

            runAllApiTests: function() {
                console.log('Running API health tests...');
                this.showNotification('Running API health tests...', 'info');
                
                // Implementation would go here
            },

            calculateROI: function() {
                console.log('Calculating ROI...');
                this.showNotification('Calculating ROI...', 'info');
                
                // Implementation would go here
            },

            validateApiKey: function(key) {
                console.log('Validating API key...');
                
                // Implementation would go here
            },

            saveSettings: function() {
                console.log('Saving settings...');
                this.showNotification('Saving settings...', 'info');
                
                // Implementation would go here
            }
        };

        // Initialize the dashboard
        Dashboard.init();

        // Make Dashboard available globally for debugging
        window.rtbcbDashboard = window.rtbcbDashboard || {};
        window.rtbcbDashboard.instance = Dashboard;

        // Debug tools
        if (rtbcbDashboard.debug) {
            console.log('Debug mode enabled');
            
            // Add debug panel
            $('body').append('<div id="rtbcb-debug" style="position:fixed;top:10px;right:10px;background:white;border:2px solid black;padding:10px;z-index:9999;font-family:monospace;font-size:12px;">' +
                '<div><strong>RTBCB Debug</strong></div>' +
                '<div>jQuery: ' + (typeof jQuery !== 'undefined' ? $.fn.jquery : 'MISSING') + '</div>' +
                '<div>Buttons: <span id="debug-button-count">0</span></div>' +
                '<div>Events: <span id="debug-event-count">0</span></div>' +
            '</div>');

            // Count buttons
            $('#debug-button-count').text($('button[data-action]').length);

            // Test if events are working by adding a counter
            var eventCount = 0;
            $(document).on('click.rtbcb-debug', 'button[data-action]', function() {
                eventCount++;
                $('#debug-event-count').text(eventCount);
            });
        }
    }

    // Test function for manual debugging
    window.testButtonClicks = function() {
        alert('Testing button clicks...');
        $('button[data-action]').each(function(i, btn) {
            $(btn).css('background', i % 2 ? 'red' : 'blue');
        });
    };

})(jQuery);