"use strict";

function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t.return && (u = t.return(), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t.return || t.return(); } finally { if (u) throw o; } } }; }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
/**
 * Fixed Unified Test Dashboard JavaScript
 * Handles all dashboard functionality with improved error handling and state management
 */
(function ($, _rtbcbDashboard$circu, _rtbcbDashboard$circu2) {
  'use strict';

  // Early validation
  var _this = this;
  if (typeof rtbcbDashboard === 'undefined') {
    console.error('rtbcbDashboard is not defined');
    return;
  }
  if (typeof jQuery === 'undefined') {
    console.error('jQuery is not available');
    return;
  }
  console.log('Test dashboard script loaded');

  // Utility functions
  var debounce = function debounce(func, delay) {
    var timeoutId;
    return function () {
      for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
        args[_key] = arguments[_key];
      }
      clearTimeout(timeoutId);
      timeoutId = setTimeout(function () {
        return func.apply(_this, args);
      }, delay);
    };
  };

  // Circuit breaker for API failures
  var circuitBreaker = {
    failures: 0,
    threshold: parseInt(((_rtbcbDashboard$circu = rtbcbDashboard.circuitBreaker) === null || _rtbcbDashboard$circu === void 0 ? void 0 : _rtbcbDashboard$circu.threshold) || 5, 10),
    resetTime: parseInt(((_rtbcbDashboard$circu2 = rtbcbDashboard.circuitBreaker) === null || _rtbcbDashboard$circu2 === void 0 ? void 0 : _rtbcbDashboard$circu2.resetTime) || 60000, 10),
    lastFailTime: 0,
    canExecute: function canExecute() {
      if (this.failures < this.threshold) return true;
      var now = Date.now();
      if (now - this.lastFailTime > this.resetTime) {
        this.reset();
        return true;
      }
      return false;
    },
    recordFailure: function recordFailure() {
      this.failures++;
      this.lastFailTime = Date.now();
      console.warn("[Circuit Breaker] Failure ".concat(this.failures, "/").concat(this.threshold));
    },
    recordSuccess: function recordSuccess() {
      this.failures = 0;
    },
    reset: function reset() {
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
    init: function init() {
      var _this2 = this;
      console.log('Dashboard initializing...');
      try {
        // Reset any stuck button states first
        this.resetAllButtonStates();
        this.bindEvents();
        this.initializeTabs();
        this.setupValidation();
        this.loadSavedState();

        // Initialize Chart.js if available
        if (typeof Chart !== 'undefined') {
          this.setupCharts();
        }

        // Add emergency reset handler (Ctrl/Cmd + R while on dashboard)
        $(document).on('keydown.rtbcb-dashboard', function (e) {
          if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
            _this2.resetAllButtonStates();
          }
        });
        console.log('Dashboard initialized successfully');
      } catch (error) {
        console.error('Dashboard initialization failed:', error);
        this.showNotification('Dashboard initialization failed. Please refresh the page.', 'error');
      }
    },
    // Bind all event handlers
    bindEvents: function bindEvents() {
      console.log('Binding events...');

      // Remove any existing handlers to prevent duplicates
      $(document).off('.rtbcb-dashboard');

      // Store reference to Dashboard object for event handlers
      var self = this;

      // Enhanced event binding for cross-platform compatibility
      this.bindCrossPlatformEvents();
      console.log('Events bound successfully');
    },
    // Enhanced cross-platform event binding
    bindCrossPlatformEvents: function bindCrossPlatformEvents() {
      var self = this;

      // Generalised action binder for both mouse and keyboard interaction
      function bindAction(selector, callback) {
        // Helper to determine if interaction should be blocked
        function isButtonInteractionBlocked($button) {
          return $button.prop('disabled') || $button.hasClass('rtbcb-loading') || self.isGenerating;
        }

        // Handle mouse clicks and keyboard activation (Enter/Space)
        $(document).on('click.rtbcb-dashboard keydown.rtbcb-dashboard', selector, function (e) {
          if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ' && e.key !== 'Space') {
            return;
          }
          var $button = $(this);
          if (isButtonInteractionBlocked($button)) {
            return;
          }
          e.preventDefault();
          e.stopPropagation();
          callback.call(this, e);
        });
      }

      // Tab navigation
      bindAction('.rtbcb-test-tabs .nav-tab', function (e) {
        var tab = $(e.currentTarget).data('tab');
        if (tab) {
          self.switchTab(tab);
        }
      });

      // Company Overview actions
      bindAction('[data-action="run-company-overview"]', function (e) {
        self.generateCompanyOverview();
      });
      bindAction('[data-action="clear-results"]', function (e) {
        self.clearResults();
      });

      // LLM Test actions
      bindAction('[data-action="run-llm-test"]', function (e) {
        self.runLLMTest();
      });

      // RAG System actions
      bindAction('[data-action="run-rag-test"]', function (e) {
        self.runRagTest();
      });
      bindAction('[data-action="rebuild-rag-index"]', function (e) {
        self.rebuildRagIndex();
      });

      // API Health actions
      bindAction('[data-action="api-health-ping"]', function (e) {
        self.runAllApiTests();
      });

      // ROI Calculator actions
      bindAction('[data-action="calculate-roi"]', function (e) {
        self.calculateROI();
      });

      // Settings actions
      $(document).on('submit.rtbcb-dashboard', '#rtbcb-dashboard-settings-form', function (e) {
        e.preventDefault();
        self.saveSettings();
      });
      bindAction('[data-action="toggle-api-key"]', function (e) {
        self.toggleApiKeyVisibility();
      });

      // Input validation (keep original for these)
      $(document).on('input.rtbcb-dashboard', '#company-name-input', debounce(function () {
        self.validateCompanyInput();
      }, 300));

      // Temperature slider
      $(document).on('input.rtbcb-dashboard', '#llm-temperature', function (e) {
        $('#llm-temperature-value').text($(e.target).val());
      });

      // Model selection
      $(document).on('change.rtbcb-dashboard', 'input[name="test-models[]"]', function () {
        self.validateLLMInputs();
      });

      // RAG query input
      $(document).on('input.rtbcb-dashboard', '#rtbcb-rag-query', debounce(function () {
        self.validateRagQuery();
      }, 300));
    },
    // Initialize tab system
    initializeTabs: function initializeTabs() {
      var hash = window.location.hash.replace('#', '');
      var validTabs = ['company-overview', 'roi-calculator', 'llm-tests', 'rag-system', 'api-health', 'data-health', 'report-preview', 'settings'];
      var targetTab = validTabs.includes(hash) ? hash : 'company-overview';
      this.switchTab(targetTab);
    },
    // Switch to specific tab
    switchTab: function switchTab(tabName) {
      if (this.isGenerating) {
        this.showNotification('Cannot switch tabs while operation is in progress', 'warning');
        return false;
      }
      console.log("Switching to tab: ".concat(tabName));
      try {
        // Update navigation
        $('.rtbcb-test-tabs .nav-tab').removeClass('nav-tab-active');
        $(".rtbcb-test-tabs .nav-tab[data-tab=\"".concat(tabName, "\"]")).addClass('nav-tab-active');

        // Hide all sections
        $('.rtbcb-test-section').removeClass('active').hide();

        // Show target section
        var $targetSection = $("#".concat(tabName));
        if ($targetSection.length) {
          $targetSection.addClass('active').show();
          this.currentTab = tabName;

          // Update URL hash
          window.location.hash = tabName;

          // Initialize tab-specific functionality
          this.initializeTabContent(tabName);
          return true;
        } else {
          console.error("Tab section not found: ".concat(tabName));
          return false;
        }
      } catch (error) {
        console.error('Error switching tabs:', error);
        this.showNotification('Error switching tabs', 'error');
        return false;
      }
    },
    // Initialize content for specific tabs
    initializeTabContent: function initializeTabContent(tabName) {
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
    setupValidation: function setupValidation() {
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
    testInitialConnection: function testInitialConnection() {
      var _rtbcbDashboard$nonce,
        _rtbcbDashboard$nonce2,
        _this3 = this;
      console.log('Testing initial API connection...');

      // Simple ping test without updating UI heavily
      var testData = {
        action: 'rtbcb_test_api_connection',
        nonce: ((_rtbcbDashboard$nonce = rtbcbDashboard.nonces) === null || _rtbcbDashboard$nonce === void 0 ? void 0 : _rtbcbDashboard$nonce.apiHealth) || ((_rtbcbDashboard$nonce2 = rtbcbDashboard.nonces) === null || _rtbcbDashboard$nonce2 === void 0 ? void 0 : _rtbcbDashboard$nonce2.dashboard) || ''
      };
      this.makeRequest(testData).then(function (response) {
        console.log('Initial API connection test passed:', response);
        _this3.updateConnectionStatus('connected');
      }).catch(function (error) {
        console.warn('Initial API connection test failed:', error.message);
        _this3.updateConnectionStatus('error', error.message);
      });
    },
    // Update connection status indicator
    updateConnectionStatus: function updateConnectionStatus(status) {
      var message = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
      var $indicators = $('.rtbcb-system-status-bar .rtbcb-status-indicator');
      var $apiIndicator = $indicators.filter(':contains("OpenAI API")');
      if ($apiIndicator.length) {
        var $icon = $apiIndicator.find('.dashicons');
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
    loadSavedState: function loadSavedState() {
      // Load any saved form data or states
      var savedCompany = localStorage.getItem('rtbcb_company_name');
      if (savedCompany) {
        $('#company-name-input').val(savedCompany);
        this.validateCompanyInput();
      }
    },
    // Company Overview functionality
    generateCompanyOverview: function generateCompanyOverview() {
      var _rtbcbDashboard$nonce3,
        _this4 = this;
      if (this.isGenerating) return;
      var companyName = $('#company-name-input').val().trim();
      if (!companyName) {
        this.showNotification('Please enter a company name', 'error');
        return;
      }
      if (!circuitBreaker.canExecute()) {
        this.showNotification('Too many failures. Please wait before trying again.', 'warning');
        return;
      }
      console.log("Generating overview for: ".concat(companyName));
      this.isGenerating = true;
      this.setButtonState('[data-action="run-company-overview"]', 'loading');
      this.startProgress();

      // Save company name
      localStorage.setItem('rtbcb_company_name', companyName);
      var requestData = {
        action: 'rtbcb_test_company_overview_enhanced',
        nonce: ((_rtbcbDashboard$nonce3 = rtbcbDashboard.nonces) === null || _rtbcbDashboard$nonce3 === void 0 ? void 0 : _rtbcbDashboard$nonce3.dashboard) || '',
        company_name: companyName,
        model: $('#model-selection').val() || 'mini',
        show_debug: $('#show-debug-info').is(':checked')
      };
      this.makeRequest(requestData).then(function (response) {
        circuitBreaker.recordSuccess();
        _this4.displayCompanyResults(response);
        _this4.setButtonState('[data-action="run-company-overview"]', 'success');
      }).catch(function (error) {
        circuitBreaker.recordFailure();
        console.error('Company overview error:', error);
        _this4.showError(error.message || 'Failed to generate overview', {
          action: 'generateCompanyOverview',
          companyName: companyName,
          model: $('#model-selection').val(),
          timestamp: new Date().toISOString(),
          error: error
        });
        _this4.setButtonState('[data-action="run-company-overview"]', 'error');
      }).finally(function () {
        _this4.isGenerating = false;
        _this4.stopProgress();
      });
    },
    // LLM Test functionality
    runLLMTest: function runLLMTest() {
      var _rtbcbDashboard$nonce4,
        _this5 = this;
      if (this.isGenerating) return;
      var prompt = $('#llm-test-prompt').val().trim();
      var selectedModels = $('input[name="test-models[]"]:checked').map(function () {
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
      console.log('Running LLM test...', {
        prompt: prompt,
        models: selectedModels
      });
      this.isGenerating = true;
      this.setButtonState('[data-action="run-llm-test"]', 'loading');
      this.startProgress();
      var requestData = {
        action: 'rtbcb_run_llm_test',
        nonce: ((_rtbcbDashboard$nonce4 = rtbcbDashboard.nonces) === null || _rtbcbDashboard$nonce4 === void 0 ? void 0 : _rtbcbDashboard$nonce4.llm) || '',
        modelIds: selectedModels,
        promptA: prompt,
        maxTokens: parseInt($('#llm-max-tokens').val()) || 1000,
        temperature: parseFloat($('#llm-temperature').val()) || 0.3,
        runMode: 'matrix'
      };
      this.makeRequest(requestData).then(function (response) {
        circuitBreaker.recordSuccess();
        _this5.displayLLMResults(response);
        _this5.setButtonState('[data-action="run-llm-test"]', 'success');
      }).catch(function (error) {
        circuitBreaker.recordFailure();
        console.error('LLM test error:', error);
        _this5.showError(error.message || 'LLM test failed');
        _this5.setButtonState('[data-action="run-llm-test"]', 'error');
      }).finally(function () {
        _this5.isGenerating = false;
        _this5.stopProgress();
      });
    },
    // RAG System functionality
    runRagTest: function runRagTest() {
      var _rtbcbDashboard$nonce5,
        _this6 = this;
      if (this.isGenerating) return;
      var query = $('#rtbcb-rag-query').val().trim();
      if (!query) {
        this.showNotification('Please enter a query', 'error');
        return;
      }
      if (!circuitBreaker.canExecute()) {
        this.showNotification('Too many failures. Please wait before trying again.', 'warning');
        return;
      }
      console.log('Running RAG test...', {
        query: query
      });
      this.isGenerating = true;
      this.setButtonState('[data-action="run-rag-test"]', 'loading');
      var requestData = {
        action: 'rtbcb_test_rag_query',
        nonce: ((_rtbcbDashboard$nonce5 = rtbcbDashboard.nonces) === null || _rtbcbDashboard$nonce5 === void 0 ? void 0 : _rtbcbDashboard$nonce5.dashboard) || '',
        query: query,
        top_k: parseInt($('#rtbcb-rag-top-k').val()) || 5,
        type: $('#rtbcb-rag-type').val() || 'all'
      };
      this.makeRequest(requestData).then(function (response) {
        circuitBreaker.recordSuccess();
        _this6.displayRagResults(response);
        _this6.setButtonState('[data-action="run-rag-test"]', 'success');
      }).catch(function (error) {
        circuitBreaker.recordFailure();
        console.error('RAG test error:', error);
        _this6.showError(error.message || 'RAG test failed');
        _this6.setButtonState('[data-action="run-rag-test"]', 'error');
      }).finally(function () {
        _this6.isGenerating = false;
      });
    },
    rebuildRagIndex: function rebuildRagIndex() {
      var _rtbcbDashboard$nonce6,
        _this7 = this;
      if (this.isGenerating) return;
      if (!circuitBreaker.canExecute()) {
        this.showNotification('Too many failures. Please wait before trying again.', 'warning');
        return;
      }
      console.log('Rebuilding RAG index...');
      this.isGenerating = true;
      this.setButtonState('[data-action="rebuild-rag-index"]', 'loading');
      var requestData = {
        action: 'rtbcb_rag_rebuild_index',
        nonce: ((_rtbcbDashboard$nonce6 = rtbcbDashboard.nonces) === null || _rtbcbDashboard$nonce6 === void 0 ? void 0 : _rtbcbDashboard$nonce6.dashboard) || ''
      };
      this.makeRequest(requestData).then(function (response) {
        circuitBreaker.recordSuccess();
        _this7.showNotification('RAG index rebuilt successfully', 'success');
        _this7.setButtonState('[data-action="rebuild-rag-index"]', 'success');
        // Update index info
        if (response.index_size) {
          $('#rtbcb-rag-index-size').text("Entries: ".concat(response.index_size));
        }
        if (response.last_indexed) {
          $('#rtbcb-rag-last-indexed').text("Last indexed: ".concat(response.last_indexed));
        }
      }).catch(function (error) {
        circuitBreaker.recordFailure();
        console.error('RAG rebuild error:', error);
        _this7.showError(error.message || 'RAG index rebuild failed');
        _this7.setButtonState('[data-action="rebuild-rag-index"]', 'error');
      }).finally(function () {
        _this7.isGenerating = false;
      });
    },
    // API Health functionality
    runAllApiTests: function runAllApiTests() {
      var _rtbcbDashboard$nonce7,
        _this8 = this;
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
      var requestData = {
        action: 'rtbcb_run_api_health_tests',
        nonce: ((_rtbcbDashboard$nonce7 = rtbcbDashboard.nonces) === null || _rtbcbDashboard$nonce7 === void 0 ? void 0 : _rtbcbDashboard$nonce7.apiHealth) || ''
      };
      this.makeRequest(requestData).then(function (response) {
        circuitBreaker.recordSuccess();
        _this8.updateApiHealthResults(response);
        _this8.setButtonState('[data-action="api-health-ping"]', 'success');
        _this8.showNotification('API health tests completed', 'success');
      }).catch(function (error) {
        circuitBreaker.recordFailure();
        console.error('API health test error:', error);
        _this8.showError(error.message || 'API health tests failed', {
          action: 'runAllApiTests',
          timestamp: new Date().toISOString(),
          error: error
        });
        _this8.setButtonState('[data-action="api-health-ping"]', 'error');
        $('#rtbcb-api-health-notice').text('API tests failed');
      }).finally(function () {
        _this8.isGenerating = false;
      });
    },
    // ROI Calculator functionality
    calculateROI: function calculateROI() {
      var _rtbcbDashboard$nonce8,
        _this9 = this;
      if (this.isGenerating) return;
      console.log('Calculating ROI...');
      this.isGenerating = true;
      this.setButtonState('[data-action="calculate-roi"]', 'loading');

      // Collect ROI form data
      var roiData = {};
      $('#roi-calculator').find('input, select').each(function () {
        if (this.id) {
          roiData[this.id] = $(this).val();
        }
      });
      var requestData = {
        action: 'rtbcb_calculate_roi_test',
        nonce: ((_rtbcbDashboard$nonce8 = rtbcbDashboard.nonces) === null || _rtbcbDashboard$nonce8 === void 0 ? void 0 : _rtbcbDashboard$nonce8.roiCalculator) || '',
        roi_data: roiData
      };
      this.makeRequest(requestData).then(function (response) {
        _this9.displayROIResults(response);
        _this9.setButtonState('[data-action="calculate-roi"]', 'success');
        _this9.showNotification('ROI calculated successfully', 'success');
      }).catch(function (error) {
        console.error('ROI calculation error:', error);
        _this9.showError(error.message || 'ROI calculation failed');
        _this9.setButtonState('[data-action="calculate-roi"]', 'error');
      }).finally(function () {
        _this9.isGenerating = false;
      });
    },
    // Settings functionality
    saveSettings: function saveSettings() {
      var _rtbcbDashboard$nonce9,
        _this0 = this;
      console.log('Saving settings...');
      var formData = new FormData(document.getElementById('rtbcb-dashboard-settings-form'));
      var requestData = {
        action: 'rtbcb_save_dashboard_settings',
        nonce: formData.get('nonce') || ((_rtbcbDashboard$nonce9 = rtbcbDashboard.nonces) === null || _rtbcbDashboard$nonce9 === void 0 ? void 0 : _rtbcbDashboard$nonce9.saveSettings)
      };

      // Add all form fields to request data
      var _iterator = _createForOfIteratorHelper(formData.entries()),
        _step;
      try {
        for (_iterator.s(); !(_step = _iterator.n()).done;) {
          var _step$value = _slicedToArray(_step.value, 2),
            key = _step$value[0],
            value = _step$value[1];
          requestData[key] = value;
        }
      } catch (err) {
        _iterator.e(err);
      } finally {
        _iterator.f();
      }
      this.makeRequest(requestData).then(function (response) {
        if (response.reload) {
          window.location.reload();
          return;
        }
        var isValid = !!response.api_valid;
        rtbcbDashboard.api_valid = isValid;
        _this0.updateApiKeyStatus(isValid);
        $('[data-action="run-company-overview"]').prop('disabled', !isValid);
        if (isValid) {
          var _rtbcbDashboard$strin;
          _this0.showNotification(response.message || ((_rtbcbDashboard$strin = rtbcbDashboard.strings) === null || _rtbcbDashboard$strin === void 0 ? void 0 : _rtbcbDashboard$strin.validApiKeySaved) || 'Valid API key saved', 'success');
        } else {
          var _rtbcbDashboard$strin2;
          _this0.showError(response.message || ((_rtbcbDashboard$strin2 = rtbcbDashboard.strings) === null || _rtbcbDashboard$strin2 === void 0 ? void 0 : _rtbcbDashboard$strin2.apiKeyValidationFailed) || 'API key validation failed');
        }
      }).catch(function (error) {
        var _rtbcbDashboard$strin3;
        console.error('Settings save error:', error);
        _this0.showError(error.message || ((_rtbcbDashboard$strin3 = rtbcbDashboard.strings) === null || _rtbcbDashboard$strin3 === void 0 ? void 0 : _rtbcbDashboard$strin3.settingsSaveFailed) || 'Failed to save settings');
      });
    },
    toggleApiKeyVisibility: function toggleApiKeyVisibility() {
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
    updateApiKeyStatus: function updateApiKeyStatus(isValid) {
      var _rtbcbDashboard$strin4, _rtbcbDashboard$strin5;
      var $status = $('#rtbcb-api-key-status');
      $status.toggleClass('status-good', isValid);
      $status.toggleClass('status-error', !isValid);
      $status.find('.dashicons').toggleClass('dashicons-yes-alt', isValid).toggleClass('dashicons-warning', !isValid);
      $status.find('.status-text').text(isValid ? ((_rtbcbDashboard$strin4 = rtbcbDashboard.strings) === null || _rtbcbDashboard$strin4 === void 0 ? void 0 : _rtbcbDashboard$strin4.valid) || 'Valid' : ((_rtbcbDashboard$strin5 = rtbcbDashboard.strings) === null || _rtbcbDashboard$strin5 === void 0 ? void 0 : _rtbcbDashboard$strin5.invalid) || 'Invalid');
    },
    // Validation methods
    validateCompanyInput: function validateCompanyInput() {
      var companyName = $('#company-name-input').val().trim();
      var isValid = companyName.length >= 2;
      $('[data-action="run-company-overview"]').prop('disabled', !isValid || this.isGenerating);
      if (companyName.length > 0 && companyName.length < 2) {
        $('#company-name-input').addClass('error');
      } else {
        $('#company-name-input').removeClass('error');
      }
    },
    validateLLMInputs: function validateLLMInputs() {
      var prompt = $('#llm-test-prompt').val().trim();
      var selectedModels = $('input[name="test-models[]"]:checked').length;
      var isValid = prompt.length > 0 && selectedModels > 0;
      $('[data-action="run-llm-test"]').prop('disabled', !isValid || this.isGenerating);
    },
    validateRagQuery: function validateRagQuery() {
      var query = $('#rtbcb-rag-query').val().trim();
      var isValid = query.length > 0;
      $('[data-action="run-rag-test"]').prop('disabled', !isValid || this.isGenerating);
    },
    // Display methods
    displayCompanyResults: function displayCompanyResults(data) {
      var $container = $('#results-container');
      var $content = $('#results-content');
      var $meta = $('#results-meta');
      if (data.overview) {
        $content.html(this.formatContent(data.overview));
      }

      // Build metadata display
      var metaItems = [];
      if (data.word_count) metaItems.push("Words: ".concat(data.word_count));
      if (data.elapsed) metaItems.push("Time: ".concat(data.elapsed, "s"));
      if (data.model_used) metaItems.push("Model: ".concat(data.model_used));
      $meta.html(metaItems.join(' | '));
      $container.show();

      // Enable action buttons
      $('[data-action="clear-results"], [data-action="export-results"]').prop('disabled', false);
    },
    displayLLMResults: function displayLLMResults(data) {
      var _this1 = this;
      console.log('Displaying LLM results:', data);
      var $container = $('#llm-test-results');
      var $tbody = $('#llm-comparison-tbody');
      $tbody.empty();
      if (data.results && Array.isArray(data.results)) {
        data.results.forEach(function (result) {
          var row = $("\n                        <tr class=\"rtbcb-result-row\">\n                            <td><strong>".concat(_this1.escapeHtml(result.model_name || result.model_key), "</strong></td>\n                            <td>").concat(result.latency || result.response_time || '--', "ms</td>\n                            <td>").concat(result.tokens_used || '--', "</td>\n                            <td>$").concat((result.cost_estimate || 0).toFixed(6), "</td>\n                            <td>").concat(result.quality_score || '--', "</td>\n                            <td class=\"rtbcb-response-preview\">").concat(_this1.escapeHtml((result.response || result.content || '').substring(0, 100)), "...</td>\n                        </tr>\n                    "));
          $tbody.append(row);
        });
      }
      $container.show();
    },
    displayRagResults: function displayRagResults(data) {
      var _this10 = this;
      console.log('Displaying RAG results:', data);
      var $container = $('#rtbcb-rag-results');
      var $tbody = $('#rtbcb-rag-results-table tbody');
      $tbody.empty();
      if (data.results && Array.isArray(data.results)) {
        data.results.forEach(function (result) {
          var _result$metadata, _result$metadata2;
          var score = parseFloat(result.score || 0);
          var statusClass = score >= 0.8 ? 'status-good' : score >= 0.5 ? 'status-warning' : 'status-error';
          var row = $("\n                        <tr class=\"".concat(statusClass, "\">\n                            <td>").concat(_this10.escapeHtml(result.type || '--'), "</td>\n                            <td>").concat(_this10.escapeHtml(result.ref_id || '--'), "</td>\n                            <td>").concat(_this10.escapeHtml(((_result$metadata = result.metadata) === null || _result$metadata === void 0 ? void 0 : _result$metadata.name) || ((_result$metadata2 = result.metadata) === null || _result$metadata2 === void 0 ? void 0 : _result$metadata2.title) || '--'), "</td>\n                            <td>").concat(score.toFixed(3), "</td>\n                        </tr>\n                    "));
          $tbody.append(row);
        });
      }

      // Update metrics
      if (data.metrics) {
        $('#rtbcb-rag-metrics').text("Time: ".concat(data.metrics.retrieval_time || 0, "ms | Results: ").concat(data.metrics.result_count || 0, " | Avg Score: ").concat((data.metrics.average_score || 0).toFixed(3)));
      }
      $container.show();
      $('[data-action="copy-rag-context"], [data-action="export-rag-results"]').prop('disabled', false);
    },
    displayROIResults: function displayROIResults(data) {
      console.log('Displaying ROI results:', data);
      var $container = $('#roi-results-container');

      // Update ROI cards
      if (data.conservative) {
        $('#roi-conservative-percent').text("".concat((data.conservative.roi_percentage || 0).toFixed(1), "%"));
        $('#roi-conservative-amount').text("$".concat((data.conservative.total_annual_benefit || 0).toLocaleString()));
      }
      if (data.base) {
        $('#roi-realistic-percent').text("".concat((data.base.roi_percentage || 0).toFixed(1), "%"));
        $('#roi-realistic-amount').text("$".concat((data.base.total_annual_benefit || 0).toLocaleString()));
      }
      if (data.optimistic) {
        $('#roi-optimistic-percent').text("".concat((data.optimistic.roi_percentage || 0).toFixed(1), "%"));
        $('#roi-optimistic-amount').text("$".concat((data.optimistic.total_annual_benefit || 0).toLocaleString()));
      }
      $container.show();
      $('[data-action="export-roi-results"]').prop('disabled', false);
    },
    updateApiHealthResults: function updateApiHealthResults(data) {
      console.log('Updating API health results:', data);
      var $notice = $('#rtbcb-api-health-notice');
      if (data.overall_status === 'all_passed') {
        $notice.text('All systems operational').removeClass('error').addClass('success');
      } else {
        $notice.text('Some issues detected').removeClass('success').addClass('error');
      }
      if (data.results && _typeof(data.results) === 'object') {
        Object.keys(data.results).forEach(function (component) {
          var result = data.results[component];
          var $row = $("#rtbcb-api-".concat(component));
          if ($row.length) {
            var $indicator = $row.find('.rtbcb-status-indicator');

            // Update status indicator
            $indicator.removeClass('status-good status-error status-warning').addClass(result.passed ? 'status-good' : 'status-error');

            // Update individual fields with fallbacks
            var $lastTested = $row.find('.rtbcb-last-tested');
            if ($lastTested.length) {
              $lastTested.text(result.last_tested || new Date().toLocaleTimeString());
            }
            var $responseTime = $row.find('.rtbcb-response-time');
            if ($responseTime.length && result.response_time) {
              $responseTime.text("".concat(result.response_time, "ms"));
            }
            var $message = $row.find('.rtbcb-message');
            if ($message.length) {
              $message.text(result.message || (result.passed ? 'OK' : 'Failed'));
            }
          }
        });
      }

      // Update last check timestamp
      var $lastCheck = $('#rtbcb-api-health-last-check');
      if ($lastCheck.length) {
        $lastCheck.text("Last checked: ".concat(new Date().toLocaleString()));
      }
    },
    // Utility methods
    clearResults: function clearResults() {
      $('#results-container, #error-container').hide();
      $('[data-action="export-results"], [data-action="clear-results"]').prop('disabled', true);
      this.showNotification('Results cleared', 'info');
    },
    // Enhanced error display with debugging options
    showError: function showError(message) {
      var _this11 = this;
      var debugInfo = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
      // Dismiss any existing notifications first
      this.dismissNotifications();
      var $container = $('#error-container');
      var $content = $('#error-content');
      var errorHtml = "<strong>Error:</strong> ".concat(this.escapeHtml(message));

      // Add debug information if available and debug mode is enabled
      if (debugInfo && ($('#show-debug-info').is(':checked') || this.debugMode)) {
        errorHtml += "\n                    <div class=\"rtbcb-debug-error\" style=\"margin-top: 10px; padding: 10px; background: #fafafa; border: 1px solid #ddd; border-radius: 4px;\">\n                        <strong>Debug Information:</strong>\n                        <details style=\"margin-top: 5px;\">\n                            <summary style=\"cursor: pointer; font-weight: bold;\">Click to expand</summary>\n                            <pre style=\"margin-top: 5px; font-size: 12px; white-space: pre-wrap;\">".concat(this.escapeHtml(JSON.stringify(debugInfo, null, 2)), "</pre>\n                        </details>\n                    </div>\n                ");
      }

      // Add troubleshooting tips for common errors
      if (message.includes('timeout') || message.includes('timed out')) {
        errorHtml += "\n                    <div class=\"rtbcb-error-tips\" style=\"margin-top: 10px; padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;\">\n                        <strong>\uD83D\uDCA1 Troubleshooting tip:</strong> Request timed out. Try refreshing the page or check your internet connection.\n                    </div>\n                ";
      } else if (message.includes('Permission denied') || message.includes('403')) {
        errorHtml += "\n                    <div class=\"rtbcb-error-tips\" style=\"margin-top: 10px; padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;\">\n                        <strong>\uD83D\uDCA1 Troubleshooting tip:</strong> Permission denied. Please refresh the page and try again.\n                    </div>\n                ";
      } else if (message.includes('API') || message.includes('Network error')) {
        errorHtml += "\n                    <div class=\"rtbcb-error-tips\" style=\"margin-top: 10px; padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;\">\n                        <strong>\uD83D\uDCA1 Troubleshooting tip:</strong> API connection issue. Check your API key configuration in Settings.\n                    </div>\n                ";
      }
      $content.html(errorHtml);
      $container.show();
      $('#results-container').hide();

      // Add a manual retry button for failed operations
      if (!$container.find('.rtbcb-retry-button').length) {
        var retryButton = $("\n                    <button type=\"button\" class=\"button button-small rtbcb-retry-button\" style=\"margin-top: 10px;\">\n                        <span class=\"dashicons dashicons-update\"></span> Retry\n                    </button>\n                ");
        retryButton.on('click', function () {
          $container.fadeOut();
          _this11.resetAllButtonStates();
        });
        $content.append(retryButton);
      }

      // Auto-dismiss error after 15 seconds (increased from 10)
      setTimeout(function () {
        $container.fadeOut();
      }, 15000);
    },
    formatContent: function formatContent(content) {
      if (!content) return '';
      return content.replace(/\n\n/g, '</p><p>').replace(/\n/g, '<br>').replace(/^/, '<p>').replace(/$/, '</p>');
    },
    escapeHtml: function escapeHtml(text) {
      var div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    },
    // Enhanced button state management
    setButtonState: function setButtonState(selector, state, text) {
      var _this12 = this;
      var $button = $(selector);
      if ($button.length === 0) {
        console.warn('Button not found:', selector);
        return;
      }

      // Store original text if not already stored
      if (!$button.data('default-text')) {
        $button.data('default-text', $button.text().trim());
      }
      var defaultText = $button.data('default-text');

      // Clear all state classes first
      $button.removeClass('rtbcb-loading rtbcb-success rtbcb-error rtbcb-touch-active');
      switch (state) {
        case 'loading':
          $button.prop('disabled', true).addClass('rtbcb-loading').css('pointer-events', 'none') // Explicitly disable pointer events
          .html("<span class=\"dashicons dashicons-update rtbcb-spin\"></span> ".concat(text || 'Loading...'));
          break;
        case 'success':
          $button.prop('disabled', false).addClass('rtbcb-success').css('pointer-events', 'auto') // Explicitly enable pointer events
          .html("<span class=\"dashicons dashicons-yes-alt\"></span> ".concat(text || 'Complete'));
          // Reset after delay
          setTimeout(function () {
            if ($button.hasClass('rtbcb-success')) {
              // Only reset if still in success state
              _this12.resetButtonState($button, defaultText);
            }
          }, 3000);
          break;
        case 'error':
          $button.prop('disabled', false).addClass('rtbcb-error').css('pointer-events', 'auto') // Explicitly enable pointer events
          .html("<span class=\"dashicons dashicons-warning\"></span> ".concat(text || 'Error'));
          // Reset after delay
          setTimeout(function () {
            if ($button.hasClass('rtbcb-error')) {
              // Only reset if still in error state
              _this12.resetButtonState($button, defaultText);
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
    resetButtonState: function resetButtonState($button, text) {
      $button.prop('disabled', false).removeClass('rtbcb-loading rtbcb-success rtbcb-error rtbcb-touch-active').css('pointer-events', 'auto').html(this.escapeHtml(text));
    },
    // Force reset all buttons (emergency cleanup)
    resetAllButtonStates: function resetAllButtonStates() {
      var _this13 = this;
      console.log('Resetting all button states...');
      $('button[data-action]').each(function (index, element) {
        var $button = $(element);
        var defaultText = $button.data('default-text') || $button.text().trim();
        _this13.resetButtonState($button, defaultText);
      });
      this.isGenerating = false;
    },
    // Progress management
    startProgress: function startProgress() {
      var _this14 = this;
      this.clearProgress();
      this.startTime = Date.now();
      $('#progress-container').show();
      $('#progress-fill').css('width', '0%');
      $('#progress-status').text('Starting...');
      var progress = 0;
      this.progressTimer = setInterval(function () {
        progress = Math.min(progress + Math.random() * 10, 90);
        $('#progress-fill').css('width', "".concat(progress, "%"));
        var elapsed = Math.floor((Date.now() - _this14.startTime) / 1000);
        $('#progress-timer').text("".concat(Math.floor(elapsed / 60), ":").concat((elapsed % 60).toString().padStart(2, '0')));
      }, 500);
    },
    stopProgress: function stopProgress() {
      this.clearProgress();
      $('#progress-fill').css('width', '100%');
      $('#progress-status').text('Complete!');
      setTimeout(function () {
        $('#progress-container').hide();
        $('#progress-fill').css('width', '0%');
        $('#progress-status').text('');
        $('#progress-timer').text('0:00');
      }, 1000);
    },
    clearProgress: function clearProgress() {
      if (this.progressTimer) {
        clearInterval(this.progressTimer);
        this.progressTimer = null;
      }
    },
    // Enhanced AJAX request handling with improved error reporting
    makeRequest: function makeRequest(data) {
      var _this15 = this;
      // Abort any existing request
      if (this.currentRequest) {
        this.currentRequest.abort();
        this.currentRequest = null;
      }
      console.log('Making API request:', data.action, data);
      return new Promise(function (resolve, reject) {
        _this15.currentRequest = $.ajax({
          url: rtbcbDashboard.ajaxurl,
          type: 'POST',
          data: data,
          timeout: 120000,
          beforeSend: function beforeSend(xhr) {
            console.log('API request started:', data.action);
          },
          success: function success(response, textStatus, xhr) {
            _this15.currentRequest = null;
            console.log('API response received:', {
              action: data.action,
              success: response.success,
              status: xhr.status,
              response: response
            });
            if (response.success) {
              resolve(response.data);
            } else {
              var errorMessage = _this15.extractErrorMessage(response);
              console.error('API request failed:', {
                action: data.action,
                error: errorMessage,
                response: response
              });
              reject(new Error(errorMessage));
            }
          },
          error: function error(xhr, status, _error) {
            _this15.currentRequest = null;

            // Don't reject if request was aborted
            if (status === 'abort') {
              console.log('Request aborted');
              return;
            }
            var errorDetails = {
              action: data.action,
              status: status,
              httpStatus: xhr.status,
              error: _error,
              responseText: xhr.responseText
            };
            console.error('API request error:', errorDetails);
            var message = _this15.getErrorMessage(xhr, status, _error);

            // Add debugging information in development
            if (typeof DEBUG !== 'undefined' && DEBUG) {
              if (console.groupCollapsed) {
                console.groupCollapsed('API Error Details');
                console.log('Status:', status);
                console.log('HTTP Status:', xhr.status);
                console.log('Error:', _error);
                console.log('Response:', xhr.responseText);
                console.log('Full XHR:', xhr);
                console.groupEnd();
              } else {
                console.log('API Error Details:', {
                  status: status,
                  httpStatus: xhr.status,
                  error: _error,
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
    extractErrorMessage: function extractErrorMessage(response) {
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
    getErrorMessage: function getErrorMessage(xhr, status, error) {
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
        var jsonResponse = JSON.parse(xhr.responseText);
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
      return "Request failed: ".concat(error || 'Unknown error', " (HTTP ").concat(xhr.status, ")");
    },
    // Chart management
    setupCharts: function setupCharts() {
      // Initialize Chart.js defaults
      if (typeof Chart !== 'undefined') {
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;
      }
    },
    createChart: function createChart(chartId, config) {
      if (typeof Chart === 'undefined') {
        console.warn('Chart.js not available');
        return null;
      }

      // Destroy existing chart
      if (this.charts[chartId]) {
        this.charts[chartId].destroy();
      }
      var ctx = document.getElementById(chartId);
      if (!ctx) {
        console.warn("Chart canvas ".concat(chartId, " not found"));
        return null;
      }
      this.charts[chartId] = new Chart(ctx, config);
      return this.charts[chartId];
    },
    // Notifications
    showNotification: function showNotification(message) {
      var type = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'info';
      // Dismiss existing notifications first
      this.dismissNotifications();
      var notification = $("\n                <div class=\"notice notice-".concat(type, " is-dismissible rtbcb-notification\">\n                    <p>").concat(this.escapeHtml(message), "</p>\n                </div>\n            "));
      $('.wrap.rtbcb-unified-test-dashboard').prepend(notification);

      // Auto-dismiss after 5 seconds
      setTimeout(function () {
        notification.fadeOut(function () {
          return notification.remove();
        });
      }, 5000);

      // Manual dismiss
      notification.find('.notice-dismiss').on('click', function () {
        notification.remove();
      });
    },
    dismissNotifications: function dismissNotifications() {
      $('.rtbcb-notification').fadeOut(function () {
        $('.rtbcb-notification').remove();
      });
    },
    updateApiHealthStatus: function updateApiHealthStatus() {
      var _rtbcbDashboard$apiHe;
      // Update API health status if on that tab
      var lastTest = (_rtbcbDashboard$apiHe = rtbcbDashboard.apiHealth) === null || _rtbcbDashboard$apiHe === void 0 ? void 0 : _rtbcbDashboard$apiHe.lastResults;
      if (lastTest) {
        this.updateApiHealthResults(lastTest);
      }
    },
    // Cleanup method
    cleanup: function cleanup() {
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
  $(document).ready(function () {
    Dashboard.init();
  });

  // Cleanup on page unload
  $(window).on('beforeunload', function () {
    Dashboard.cleanup();
  });

  // Expose for debugging
  window.RTBCBDashboard = Dashboard;
})(jQuery);
