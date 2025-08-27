// WordPress admin script - needs IE11+ compatibility
// Convert all ES6+ features to ES5 syntax
// Ensure proper minification compatibility
"use strict";

function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _await(value, then, direct) {
  if (direct) {
    return then ? then(value) : value;
  }
  if (!value || !value.then) {
    value = Promise.resolve(value);
  }
  return then ? value.then(then) : value;
}
function _catch(body, recover) {
  try {
    var result = body();
  } catch (e) {
    return recover(e);
  }
  if (result && result.then) {
    return result.then(void 0, recover);
  }
  return result;
}
function _empty() {}
function _continueIgnored(value) {
  if (value && value.then) {
    return value.then(_empty);
  }
}
function _async(f) {
  return function () {
    for (var args = [], i = 0; i < arguments.length; i++) {
      args[i] = arguments[i];
    }
    try {
      return Promise.resolve(f.apply(this, args));
    } catch (e) {
      return Promise.reject(e);
    }
  };
}
function _invokeIgnored(body) {
  var result = body();
  if (result && result.then) {
    return result.then(_empty);
  }
}
function _continue(value, then) {
  return value && value.then ? value.then(then) : then(value);
}
var _iteratorSymbol = /*#__PURE__*/typeof Symbol !== "undefined" ? Symbol.iterator || (Symbol.iterator = Symbol("Symbol.iterator")) : "@@iterator";
function _settle(pact, state, value) {
  if (!pact.s) {
    if (value instanceof _Pact) {
      if (value.s) {
        if (state & 1) {
          state = value.s;
        }
        value = value.v;
      } else {
        value.o = _settle.bind(null, pact, state);
        return;
      }
    }
    if (value && value.then) {
      value.then(_settle.bind(null, pact, state), _settle.bind(null, pact, 2));
      return;
    }
    pact.s = state;
    pact.v = value;
    var observer = pact.o;
    if (observer) {
      observer(pact);
    }
  }
}
var _Pact = /*#__PURE__*/function () {
  function _Pact() {}
  _Pact.prototype.then = function (onFulfilled, onRejected) {
    var result = new _Pact();
    var state = this.s;
    if (state) {
      var callback = state & 1 ? onFulfilled : onRejected;
      if (callback) {
        try {
          _settle(result, 1, callback(this.v));
        } catch (e) {
          _settle(result, 2, e);
        }
        return result;
      } else {
        return this;
      }
    }
    this.o = function (_this) {
      try {
        var value = _this.v;
        if (_this.s & 1) {
          _settle(result, 1, onFulfilled ? onFulfilled(value) : value);
        } else if (onRejected) {
          _settle(result, 1, onRejected(value));
        } else {
          _settle(result, 2, value);
        }
      } catch (e) {
        _settle(result, 2, e);
      }
    };
    return result;
  };
  return _Pact;
}();
function _isSettledPact(thenable) {
  return thenable instanceof _Pact && thenable.s & 1;
}
function _forTo(array, body, check) {
  var i = -1,
    pact,
    reject;
  function _cycle(result) {
    try {
      while (++i < array.length && (!check || !check())) {
        result = body(i);
        if (result && result.then) {
          if (_isSettledPact(result)) {
            result = result.v;
          } else {
            result.then(_cycle, reject || (reject = _settle.bind(null, pact = new _Pact(), 2)));
            return;
          }
        }
      }
      if (pact) {
        _settle(pact, 1, result);
      } else {
        pact = result;
      }
    } catch (e) {
      _settle(pact || (pact = new _Pact()), 2, e);
    }
  }
  _cycle();
  return pact;
}
function _forOf(target, body, check) {
  if (typeof target[_iteratorSymbol] === "function") {
    var _cycle2 = function _cycle(result) {
      try {
        while (!(step = iterator.next()).done && (!check || !check())) {
          result = body(step.value);
          if (result && result.then) {
            if (_isSettledPact(result)) {
              result = result.v;
            } else {
              result.then(_cycle2, reject || (reject = _settle.bind(null, pact = new _Pact(), 2)));
              return;
            }
          }
        }
        if (pact) {
          _settle(pact, 1, result);
        } else {
          pact = result;
        }
      } catch (e) {
        _settle(pact || (pact = new _Pact()), 2, e);
      }
    };
    var iterator = target[_iteratorSymbol](),
      step,
      pact,
      reject;
    _cycle2();
    if (iterator["return"]) {
      var _fixup = function _fixup(value) {
        try {
          if (!step.done) {
            iterator["return"]();
          }
        } catch (e) {}
        return value;
      };
      if (pact && pact.then) {
        return pact.then(_fixup, function (e) {
          throw _fixup(e);
        });
      }
      _fixup();
    }
    return pact;
  }
  // No support for Symbol.iterator
  if (!("length" in target)) {
    throw new TypeError("Object is not iterable");
  }
  // Handle live collections properly
  var values = [];
  for (var i = 0; i < target.length; i++) {
    values.push(target[i]);
  }
  return _forTo(values, function (i) {
    return body(values[i]);
  }, check);
}
function _awaitIgnored(value, direct) {
  if (!direct) {
    return value && value.then ? value.then(_empty) : Promise.resolve();
  }
}
function _invoke(body, then) {
  var result = body();
  if (result && result.then) {
    return result.then(then);
  }
  return then(result);
}
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
(function ($) {
  'use strict';

  var RTBCBAdmin = {
    utils: {
      setLoading: function setLoading(button, text) {
        var original = button.text();
        button.prop('disabled', true).text(text);
        return original;
      },
      clearLoading: function clearLoading(button, original) {
        button.prop('disabled', false).text(original);
      },
      buildResult: function buildResult(text, start, form) {
        var meta = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : {};
        var wordCount = meta.word_count || (text.trim() ? text.trim().split(/\s+/).length : 0);
        var duration = meta.elapsed || ((performance.now() - start) / 1000).toFixed(2);
        var timestamp = meta.generated ? new Date(meta.generated).toLocaleTimeString() : new Date().toLocaleTimeString();
        var container = $('<div class="rtbcb-results" />');
        container.append($('<p />').text(text));
        container.append($('<p class="rtbcb-result-meta" />').text('Word count: ' + wordCount + ' | Duration: ' + duration + 's | Time: ' + timestamp));
        var actions = $('<p class="rtbcb-result-actions" />');
        var regen = $('<button type="button" class="button" />').text(rtbcbAdmin.strings.regenerate || 'Regenerate');
        var copy = $('<button type="button" class="button" />').text(rtbcbAdmin.strings.copy_text || 'Copy Text');
        regen.on('click', function () {
          form.trigger('submit');
        });
        copy.on('click', _async(function () {
          return _continueIgnored(_catch(function () {
            return _await(navigator.clipboard.writeText(text), function () {
              alert(rtbcbAdmin.strings.copied);
            });
          }, function (err) {
            alert(rtbcbAdmin.strings.error + ' ' + err.message);
          }));
        }));
        actions.append(regen).append(' ').append(copy);
        container.append(actions);
        return container;
      },
      bindClear: function bindClear(clearBtn, results) {
        if (clearBtn.length) {
          clearBtn.on('click', function () {
            results.empty();
          });
        }
      }
    },
    runCommentaryTest: function runCommentaryTest(e) {
      e.preventDefault();
      var button = $(e.currentTarget);
      var industry = $('#rtbcb-commentary-industry').val();
      var nonce = rtbcbAdmin.company_overview_nonce;
      var results = $('#rtbcb-commentary-results');
      var original = button.text();
      button.prop('disabled', true).text(rtbcbAdmin.strings.generating);
      var formData = new FormData();
      formData.append('action', 'rtbcb_test_company_overview');
      formData.append('industry', industry);
      formData.append('nonce', nonce);
      fetch(rtbcbAdmin.ajax_url, {
        method: 'POST',
        body: formData
      }).then(function (response) {
        if (!response.ok) {
          throw new Error('Server responded ' + response.status);
        }
        return response.json();
      }).then(function (data) {
        if (data.success) {
          var overview = data.data.overview || '';
          results.text(overview);
          if (navigator.clipboard) {
            navigator.clipboard.writeText(overview).then(function () {
              alert(rtbcbAdmin.strings.copied);
            })["catch"](function () {});
          }
        } else {
          var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
          alert(message);
        }
      })["catch"](function (err) {
        alert(rtbcbAdmin.strings.error + ' ' + err.message);
      }).then(function () {
        button.prop('disabled', false).text(original);
      });
    },
    runCompanyOverviewTest: function runCompanyOverviewTest(e) {
      e.preventDefault();
      var form = $(e.currentTarget);
      var results = $('#rtbcb-company-overview-results');
      var submitBtn = form.find('button[type="submit"]');
      var original = RTBCBAdmin.utils.setLoading(submitBtn, rtbcbAdmin.strings.processing);
      var company = $('#rtbcb-company-name').val();
      var nonce = form.find('[name="nonce"]').val();
      var start = performance.now();
      var formData = new FormData();
      formData.append('action', 'rtbcb_test_company_overview');
      formData.append('company_name', company);
      formData.append('nonce', nonce);
      fetch(rtbcbAdmin.ajax_url, {
        method: 'POST',
        body: formData
      }).then(function (response) {
        if (!response.ok) {
          throw new Error('Server responded ' + response.status);
        }
        return response.json();
      }).then(function (data) {
        if (data.success) {
          var text = data.data && data.data.overview ? data.data.overview : '';
          results.html(RTBCBAdmin.utils.buildResult(text, start, form, data.data));
        } else {
          var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
          results.html('<div class="notice notice-error"><p>' + message + '</p></div>');
        }
      })["catch"](function (err) {
        results.html('<div class="notice notice-error"><p>' + rtbcbAdmin.strings.error + ' ' + err.message + '</p></div>');
      }).then(function () {
        RTBCBAdmin.utils.clearLoading(submitBtn, original);
      });
    },
    runIndustryOverviewTest: function runIndustryOverviewTest(e) {
      e.preventDefault();
      var form = $(e.currentTarget);
      var results = $('#rtbcb-industry-overview-results');
      var submitBtn = form.find('button[type="submit"]');
      var original = RTBCBAdmin.utils.setLoading(submitBtn, rtbcbAdmin.strings.processing);
      var company = Object.assign({}, rtbcbAdmin.company || {});
      company.industry = $('#rtbcb-industry-name').val();
      var nonce = form.find('[name="nonce"]').val();
      if (!company.industry) {
        results.html('<div class="notice notice-error"><p>' + rtbcbAdmin.strings.error + '</p></div>');
        RTBCBAdmin.utils.clearLoading(submitBtn, original);
        return;
      }
      var start = performance.now();
      var formData = new FormData();
      formData.append('action', 'rtbcb_test_industry_overview');
      formData.append('company_data', JSON.stringify(company));
      formData.append('nonce', nonce);
      fetch(rtbcbAdmin.ajax_url, {
        method: 'POST',
        body: formData
      }).then(function (response) {
        if (!response.ok) {
          throw new Error('Server responded ' + response.status);
        }
        return response.json();
      }).then(function (data) {
        if (data.success) {
          var text = data.data && data.data.overview ? data.data.overview : '';
          results.html(RTBCBAdmin.utils.buildResult(text, start, form, data.data));
        } else {
          var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
          results.html('<div class="notice notice-error"><p>' + message + '</p></div>');
        }
      })["catch"](function (err) {
        results.html('<div class="notice notice-error"><p>' + rtbcbAdmin.strings.error + ' ' + err.message + '</p></div>');
      }).then(function () {
        RTBCBAdmin.utils.clearLoading(submitBtn, original);
      });
    },
    runBenefitsEstimateTest: function runBenefitsEstimateTest(e) {
      e.preventDefault();
      var results = $('#rtbcb-benefits-estimate-results');
      results.text(rtbcbAdmin.strings.processing);
      var data = {
        action: 'rtbcb_test_estimated_benefits',
        company_data: {
          revenue: $('#rtbcb-test-revenue').val(),
          staff_count: $('#rtbcb-test-staff-count').val(),
          efficiency: $('#rtbcb-test-efficiency').val()
        },
        recommended_category: $('#rtbcb-test-category').val(),
        nonce: rtbcbAdmin.benefits_estimate_nonce
      };
      $.post(rtbcbAdmin.ajax_url, data).done(function (response) {
        if (response && response.success) {
          results.text(JSON.stringify(response.data.estimate || response.data));
        } else {
          var message = response && response.data && response.data.message ? response.data.message : rtbcbAdmin.strings.error;
          results.text(message);
        }
      }).fail(function () {
        results.text(rtbcbAdmin.strings.error);
      });
    },
    init: function init() {
      this.bindDashboardActions();
      this.bindExportButtons();
      this.initLeadsManager();
      this.bindDiagnosticsButton();
      this.bindReportPreview();
      this.bindSampleReport();
      this.bindSyncLocal();
      this.bindCommentaryTest();
      this.bindCompanyOverviewTest();
      this.bindIndustryOverviewTest();
      this.bindBenefitsEstimateTest();
      this.bindTestDashboard();
    },
    bindDashboardActions: function bindDashboardActions() {
      $('#rtbcb-test-api').on('click', this.testApiConnection);
      $('#rtbcb-rebuild-index').on('click', this.rebuildIndex);
      $('#rtbcb-export-data').on('click', this.exportLeads);
    },
    bindExportButtons: function bindExportButtons() {
      $('#rtbcb-export-leads').on('click', this.exportLeads);
    },
    bindDiagnosticsButton: function bindDiagnosticsButton() {
      $('#rtbcb-run-tests').on('click', this.runDiagnostics);
    },
    bindSyncLocal: function bindSyncLocal() {
      $('#rtbcb-sync-local').on('click', this.syncToLocal);
    },
    bindCommentaryTest: function bindCommentaryTest() {
      var button = $('#rtbcb-generate-commentary');
      if (!button.length) {
        return;
      }
      button.on('click', RTBCBAdmin.runCommentaryTest);
    },
    bindCompanyOverviewTest: function bindCompanyOverviewTest() {
      var form = $('#rtbcb-company-overview-form');
      if (!form.length) {
        return;
      }
      var results = $('#rtbcb-company-overview-results');
      var clearBtn = $('#rtbcb-clear-results');
      RTBCBAdmin.utils.bindClear(clearBtn, results);
      form.on('submit', RTBCBAdmin.runCompanyOverviewTest);
    },
    bindIndustryOverviewTest: function bindIndustryOverviewTest() {
      var form = $('#rtbcb-industry-overview-form');
      if (!form.length) {
        return;
      }
      var results = $('#rtbcb-industry-overview-results');
      var clearBtn = $('#rtbcb-clear-results');
      RTBCBAdmin.utils.bindClear(clearBtn, results);
      form.on('submit', RTBCBAdmin.runIndustryOverviewTest);
    },
    bindBenefitsEstimateTest: function bindBenefitsEstimateTest() {
      var form = $('#rtbcb-benefits-estimate-form');
      if (!form.length) {
        return;
      }
      form.on('submit', RTBCBAdmin.runBenefitsEstimateTest);
    },
    bindTestDashboard: function bindTestDashboard() {
      var button = $('#rtbcb-test-all-sections');
      if (!button.length) {
        return;
      }
      var status = $('#rtbcb-test-status');
      var tableBody = $('#rtbcb-test-results-summary tbody');
      var cancelButton = $('#rtbcb-cancel-tests');
      var progressBar = $('#rtbcb-test-progress');
      var controller = null;
      var originalText = button.text();
      var sectionMap = {
        'Company Overview': 'rtbcb-test-company-overview',
        'Treasury Tech Overview': 'rtbcb-test-treasury-tech-overview',
        'Industry Overview': 'rtbcb-test-industry-overview',
        'Real Treasury Overview': 'rtbcb-test-real-treasury-overview',
        'Recommended Category': 'rtbcb-test-recommended-category',
        'Estimated Benefits': 'rtbcb-test-estimated-benefits'
      };
      var runTests = _async(function () {
        controller = new AbortController();
        var companyName = $('#rtbcb-company-name').val().trim() || (rtbcbAdmin.company && rtbcbAdmin.company.name ? rtbcbAdmin.company.name : '').trim();
        if (!companyName) {
          alert('Please enter a company name.');
          status.text('');
          button.prop('disabled', false).text(originalText);
          cancelButton.hide();
          progressBar.hide();
          return;
        }
        var tests = [{
          action: 'rtbcb_test_company_overview',
          nonce: rtbcbAdmin.company_overview_nonce,
          label: 'Company Overview'
        }, {
          action: 'rtbcb_test_treasury_tech_overview',
          nonce: rtbcbAdmin.treasury_tech_overview_nonce,
          label: 'Treasury Tech Overview'
        }, {
          action: 'rtbcb_test_industry_overview',
          nonce: rtbcbAdmin.industry_overview_nonce,
          label: 'Industry Overview'
        }];
        var results = [];
        var completed = 0;
        progressBar.attr('max', tests.length).val(0).show();
        cancelButton.show().prop('disabled', false);
        return _continue(_forOf(tests, function (test) {
          if (controller.signal.aborted) {
            return;
          }
          status.text('Testing ' + test.label + '...');
          return _continueIgnored(_catch(function () {
            var formData = new FormData();
            formData.append('action', test.action);
            formData.append('nonce', test.nonce);
            if (test.action === 'rtbcb_test_company_overview') {
              formData.append('company_name', companyName);
            }
            return _await(fetch(rtbcbAdmin.ajax_url, {
              method: 'POST',
              body: formData,
              signal: controller.signal
            }), function (response) {
              if (!response.ok) {
                throw new Error('Server responded ' + response.status);
              }
              return _await(response.json(), function (data) {
                var message = data && data.data && data.data.message ? data.data.message : '';
                results.push({
                  section: test.label,
                  status: data.success ? 'success' : 'error',
                  message: message
                });
              });
            });
          }, function (err) {
            if (err.name !== 'AbortError') {
              results.push({
                section: test.label,
                status: 'error',
                message: err.message
              });
            }
            controller.abort();
          }), function () {
            completed++;
            progressBar.val(completed);
          });
        }), function () {
          cancelButton.hide();
          progressBar.hide();
          if (controller.signal.aborted) {
            status.text('Testing cancelled.');
            button.prop('disabled', false).text(originalText);
            return;
          }
          status.text('Saving results...');
          return _continue(_catch(function () {
            var saveData = new FormData();
            saveData.append('action', 'rtbcb_save_test_results');
            saveData.append('nonce', rtbcbAdmin.test_dashboard_nonce);
            saveData.append('results', JSON.stringify(results));
            return _awaitIgnored(fetch(rtbcbAdmin.ajax_url, {
              method: 'POST',
              body: saveData
            }));
          }, _empty), function () {
            tableBody.empty();
            results.forEach(function (item) {
              var sectionId = sectionMap[item.section] || '';
              var actions = sectionId ? '<a href="#' + sectionId + '" class="rtbcb-jump-tab">' + rtbcbAdmin.strings.view + '</a> | <a href="#" class="rtbcb-rerun-test" data-section="' + sectionId + '">' + rtbcbAdmin.strings.rerun + '</a>' : '';
              var row = '<tr><td>' + item.section + '</td><td>' + item.status + '</td><td>' + item.message + '</td><td>' + new Date().toLocaleString() + '</td><td>' + actions + '</td></tr>';
              tableBody.append(row);
            });
            status.text('');
            button.prop('disabled', false).text(originalText);
            $(document).trigger('rtbcb-tests-complete');
          });
        });
      });
      cancelButton.on('click', function () {
        if (controller) {
          controller.abort();
          cancelButton.prop('disabled', true);
        }
      });
      button.on('click', function () {
        button.prop('disabled', true).text(rtbcbAdmin.strings.testing);
        runTests();
      });
    },
    testApiConnection: function testApiConnection(e) {
      try {
        var _exit4 = false;
        var _this = this;
        e.preventDefault();
        var button = $(_this);
        var label = button.find('h4');
        var original = label.text();
        label.text(rtbcbAdmin.strings.processing);
        button.prop('disabled', true);
        return _await(_continue(_catch(function () {
          var formData = new FormData();
          formData.append('action', 'rtbcb_test_connection');
          formData.append('nonce', rtbcbAdmin.nonce);
          return _await(fetch(rtbcbAdmin.ajax_url, {
            method: 'POST',
            body: formData
          }), function (response) {
            if (!response.ok) {
              throw new Error("Server responded ".concat(response.status));
            }
            return _await(response.json(), function (data) {
              var errMsg = data.data && data.data.message ? data.data.message : '';
              alert(data.success ? 'API connection successful!' : rtbcbAdmin.strings.error + errMsg);
            });
          });
        }, function (err) {
          alert("".concat(rtbcbAdmin.strings.error, " ").concat(err.message));
        }), function (_result4) {
          if (_exit4) return _result4;
          label.text(original);
          button.prop('disabled', false);
        }));
      } catch (e) {
        return Promise.reject(e);
      }
    },
    rebuildIndex: function rebuildIndex(e) {
      try {
        var _exit5 = false;
        var _this2 = this;
        e.preventDefault();
        var button = $(_this2);
        var original = button.text();
        button.text(rtbcbAdmin.strings.processing).prop('disabled', true);
        return _await(_continue(_catch(function () {
          var formData = new FormData();
          formData.append('action', 'rtbcb_rebuild_index');
          formData.append('nonce', rtbcbAdmin.nonce);
          return _await(fetch(rtbcbAdmin.ajax_url, {
            method: 'POST',
            body: formData
          }), function (response) {
            if (!response.ok) {
              throw new Error("Server responded ".concat(response.status));
            }
            return _await(response.json(), function (data) {
              if (data.success) {
                alert('RAG index rebuilt successfully');
                location.reload();
              } else {
                var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                alert(message);
              }
            });
          });
        }, function (err) {
          alert("".concat(rtbcbAdmin.strings.error, " ").concat(err.message));
        }), function (_result5) {
          if (_exit5) return _result5;
          button.text(original).prop('disabled', false);
        }));
      } catch (e) {
        return Promise.reject(e);
      }
    },
    exportLeads: function exportLeads(e) {
      try {
        var _exit6 = false;
        var _this3 = this;
        e.preventDefault();
        var button = $(_this3);
        var label = button.find('h4').length ? button.find('h4') : button;
        var original = label.text();
        label.text(rtbcbAdmin.strings.processing);
        button.prop('disabled', true);
        return _await(_continue(_catch(function () {
          var params = new URLSearchParams(window.location.search);
          var formData = new FormData();
          formData.append('action', 'rtbcb_export_leads');
          formData.append('nonce', rtbcbAdmin.nonce);
          formData.append('search', params.get('search') || '');
          formData.append('category', params.get('category') || '');
          formData.append('date_from', params.get('date_from') || '');
          formData.append('date_to', params.get('date_to') || '');
          return _await(fetch(rtbcbAdmin.ajax_url, {
            method: 'POST',
            body: formData
          }), function (response) {
            if (!response.ok) {
              throw new Error("Server responded ".concat(response.status));
            }
            return _await(response.json(), function (data) {
              if (data.success) {
                var blob = new Blob([data.data.content], {
                  type: 'text/csv'
                });
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = data.data.filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
              } else {
                var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                alert(message);
              }
            });
          });
        }, function (err) {
          alert("".concat(rtbcbAdmin.strings.error, " ").concat(err.message));
        }), function (_result6) {
          if (_exit6) return _result6;
          label.text(original);
          button.prop('disabled', false);
        }));
      } catch (e) {
        return Promise.reject(e);
      }
    },
    runDiagnostics: function runDiagnostics(e) {
      try {
        var _exit7 = false;
        var _this4 = this;
        e.preventDefault();
        var button = $(_this4);
        button.prop('disabled', true);
        return _await(_continue(_catch(function () {
          var formData = new FormData();
          formData.append('action', 'rtbcb_run_tests');
          formData.append('nonce', $(_this4).data('nonce') || rtbcbAdmin.nonce);
          return _await(fetch(rtbcbAdmin.ajax_url, {
            method: 'POST',
            body: formData
          }), function (response) {
            if (!response.ok) {
              throw new Error("Server responded ".concat(response.status));
            }
            return _await(response.json(), function (data) {
              if (data.success) {
                var message = '';
                for (var _i = 0, _Object$entries = Object.entries(data.data); _i < _Object$entries.length; _i++) {
                  var _Object$entries$_i = _slicedToArray(_Object$entries[_i], 2),
                    key = _Object$entries$_i[0],
                    result = _Object$entries$_i[1];
                  message += "".concat(key, ": ").concat(result.passed ? 'PASS' : 'FAIL', " - ").concat(result.message, "\n");
                }
                alert(message);
                console.log('Diagnostics results:', data.data);
              } else {
                var _message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                alert(_message);
              }
            });
          });
        }, function (err) {
          console.error('Diagnostics error:', err);
          alert("".concat(rtbcbAdmin.strings.error, " ").concat(err.message));
        }), function (_result7) {
          if (_exit7) return _result7;
          button.prop('disabled', false);
        }));
      } catch (e) {
        return Promise.reject(e);
      }
    },
    syncToLocal: function syncToLocal(e) {
      try {
        var _exit8 = false;
        var _this5 = this;
        e.preventDefault();
        var button = $(_this5);
        var original = button.text();
        button.text(rtbcbAdmin.strings.processing).prop('disabled', true);
        return _await(_continue(_catch(function () {
          var nonce = $('#rtbcb-sync-local-form').find('input[name="rtbcb_sync_local_nonce"]').val();
          var formData = new FormData();
          formData.append('action', 'rtbcb_sync_to_local');
          formData.append('nonce', nonce);
          return _await(fetch(rtbcbAdmin.ajax_url, {
            method: 'POST',
            body: formData
          }), function (response) {
            if (!response.ok) {
              throw new Error("Server responded ".concat(response.status));
            }
            return _await(response.json(), function (data) {
              var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
              alert(message);
            });
          });
        }, function (err) {
          alert("".concat(rtbcbAdmin.strings.error, " ").concat(err.message));
        }), function (_result8) {
          if (_exit8) return _result8;
          button.text(original).prop('disabled', false);
        }));
      } catch (e) {
        return Promise.reject(e);
      }
    },
    initLeadsManager: function initLeadsManager() {
      if (document.querySelector('#rtbcb-bulk-form')) {
        new RTBCBLeadsManager();
      }
    },
    bindReportPreview: function bindReportPreview() {
      var form = document.getElementById('rtbcb-report-preview-form');
      if (!form) {
        return;
      }
      form.addEventListener('submit', this.generateReportPreview.bind(this));
      var downloadBtn = document.getElementById('rtbcb-download-pdf');
      if (downloadBtn) {
        downloadBtn.addEventListener('click', this.downloadReportPDF.bind(this));
      }
      var select = document.getElementById('rtbcb-sample-select');
      if (select) {
        var injectSample = function injectSample() {
          var key = select.value;
          var target = document.getElementById('rtbcb-sample-context');
          if (key && target && rtbcbAdmin.sampleForms && rtbcbAdmin.sampleForms[key]) {
            target.value = JSON.stringify(rtbcbAdmin.sampleForms[key], null, 2);
          }
        };
        select.addEventListener('change', injectSample);
        var loadSample = document.getElementById('rtbcb-load-sample');
        if (loadSample) {
          loadSample.addEventListener('click', injectSample);
        }
      }
    },
    generateReportPreview: function generateReportPreview(e) {
      try {
        var _exit9 = false;
        e.preventDefault();
        var form = e.currentTarget;
        var button = document.getElementById('rtbcb-generate-report');
        var original = button.textContent;
        button.textContent = rtbcbAdmin.strings.processing;
        button.disabled = true;
        return _await(_continue(_catch(function () {
          var formData = new FormData(form);
          var select = document.getElementById('rtbcb-sample-select');
          var sampleKey = select && select.value ? select.value.trim() : '';
          var action = 'rtbcb_generate_report_preview';
          if (sampleKey === '') {
            formData.set('action', action);
          } else {
            action = 'rtbcb_generate_sample_report';
            formData.set('action', action);
            formData.append('scenario_key', sampleKey);
          }
          return _await(fetch(rtbcbAdmin.ajax_url, {
            method: 'POST',
            body: formData
          }), function (response) {
            var _exit0 = false;
            return _invoke(function () {
              if (!response.ok) {
                return _await(response.text(), function (text) {
                  var requestDetails = {
                    action: action,
                    scenario_key: sampleKey
                  };
                  console.error('generateReportPreview failed:', response.status, text, requestDetails);
                  alert("".concat(rtbcbAdmin.strings.error, " ").concat(response.status, ": ").concat(text));
                  _exit9 = true;
                });
              }
            }, function (_result0) {
              return _exit0 ? _result0 : _await(response.json(), function (data) {
                if (data.success) {
                  var iframe = document.getElementById('rtbcb-report-iframe');
                  if (iframe) {
                    iframe.srcdoc = data.data.html || data.data.report_html;
                  }
                  document.getElementById('rtbcb-report-preview-card').style.display = 'block';
                  document.getElementById('rtbcb-download-pdf').style.display = 'inline-block';
                } else {
                  var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                  alert(message);
                }
              });
            });
          });
        }, function (err) {
          console.error('generateReportPreview exception:', err);
          alert("".concat(rtbcbAdmin.strings.error, " ").concat(err.message));
        }), function (_result9) {
          if (_exit9) return _result9;
          button.textContent = original;
          button.disabled = false;
        }));
      } catch (e) {
        return Promise.reject(e);
      }
    },
    bindSampleReport: function bindSampleReport() {
      var button = document.getElementById('rtbcb-generate-sample-report');
      if (!button) {
        return;
      }
      button.addEventListener('click', this.generateSampleReport.bind(this));
    },
    generateSampleReport: function generateSampleReport(e) {
      try {
        var _exit1 = false;
        e.preventDefault();
        var button = e.currentTarget;
        var original = button.textContent;
        button.textContent = rtbcbAdmin.strings.processing;
        button.disabled = true;
        return _await(_continue(_catch(function () {
          var formData = new FormData();
          var nonceField = document.getElementById('nonce');
          var nonce = nonceField ? nonceField.value : rtbcbAdmin && rtbcbAdmin.report_preview_nonce ? rtbcbAdmin.report_preview_nonce : '';
          var action = 'rtbcb_generate_sample_report';
          formData.append('action', action);
          formData.append('nonce', nonce);
          return _await(fetch(rtbcbAdmin.ajax_url, {
            method: 'POST',
            body: formData
          }), function (response) {
            var _exit10 = false;
            return _invoke(function () {
              if (!response.ok) {
                return _await(response.text(), function (text) {
                  var requestDetails = {
                    action: action,
                    nonce: nonce
                  };
                  console.error('generateSampleReport failed:', response.status, text, requestDetails);
                  alert("".concat(rtbcbAdmin.strings.error, " ").concat(response.status, ": ").concat(text));
                  _exit1 = true;
                });
              }
            }, function (_result10) {
              return _exit10 ? _result10 : _await(response.json(), function (data) {
                if (data.success) {
                  var iframe = document.getElementById('rtbcb-sample-report-frame');
                  if (iframe) {
                    iframe.srcdoc = data.data.report_html;
                  }
                } else {
                  var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                  alert(message);
                }
              });
            });
          });
        }, function (err) {
          console.error('generateSampleReport exception:', err);
          alert("".concat(rtbcbAdmin.strings.error, " ").concat(err.message));
        }), function (_result1) {
          if (_exit1) return _result1;
          button.textContent = original;
          button.disabled = false;
        }));
      } catch (e) {
        return Promise.reject(e);
      }
    },
    downloadReportPDF: function downloadReportPDF(e) {
      e.preventDefault();
      var iframe = document.getElementById('rtbcb-report-iframe');
      if (iframe && iframe.contentWindow) {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
      }
    },
    closeModal: function closeModal() {
      var modal = document.getElementById('rtbcb-lead-modal');
      if (modal) {
        modal.style.display = 'none';
      }
    }
  };
  var RTBCBLeadsManager = /*#__PURE__*/function () {
    function RTBCBLeadsManager() {
      _classCallCheck(this, RTBCBLeadsManager);
      this.bindEvents();
      this.updateBulkActionButton();
    }
    return _createClass(RTBCBLeadsManager, [{
      key: "bindEvents",
      value: function bindEvents() {
        var _this6 = this;
        var selectAll = document.getElementById('rtbcb-select-all');
        if (selectAll) {
          selectAll.addEventListener('change', this.toggleSelectAll.bind(this));
        }
        document.querySelectorAll('.rtbcb-lead-checkbox').forEach(function (cb) {
          cb.addEventListener('change', _this6.updateSelectAll.bind(_this6));
          cb.addEventListener('change', _this6.updateBulkActionButton.bind(_this6));
        });
        var bulkForm = document.getElementById('rtbcb-bulk-form');
        if (bulkForm) {
          bulkForm.addEventListener('submit', this.handleBulkAction.bind(this));
        }
        Array.from(document.querySelectorAll('.rtbcb-view-lead')).forEach(function (btn) {
          btn.addEventListener('click', _this6.viewLeadDetails.bind(_this6));
        });
        Array.from(document.querySelectorAll('.rtbcb-delete-lead')).forEach(function (btn) {
          btn.addEventListener('click', _this6.deleteLead.bind(_this6));
        });
        var modalClose = document.querySelector('.rtbcb-modal-close');
        if (modalClose) {
          modalClose.addEventListener('click', RTBCBAdmin.closeModal.bind(RTBCBAdmin));
        }
        var leadModal = document.getElementById('rtbcb-lead-modal');
        if (leadModal) {
          leadModal.addEventListener('click', function (e) {
            if (e.target.id === 'rtbcb-lead-modal') {
              RTBCBAdmin.closeModal();
            }
          });
        }
      }
    }, {
      key: "toggleSelectAll",
      value: function toggleSelectAll(e) {
        var checked = e.target.checked;
        Array.from(document.querySelectorAll('.rtbcb-lead-checkbox')).forEach(function (cb) {
          cb.checked = checked;
        });
        this.updateBulkActionButton();
      }
    }, {
      key: "updateSelectAll",
      value: function updateSelectAll() {
        var boxes = document.querySelectorAll('.rtbcb-lead-checkbox');
        var checked = document.querySelectorAll('.rtbcb-lead-checkbox:checked');
        var selectAll = document.getElementById('rtbcb-select-all');
        if (selectAll) {
          selectAll.checked = boxes.length === checked.length && boxes.length > 0;
          selectAll.indeterminate = checked.length > 0 && checked.length < boxes.length;
        }
      }
    }, {
      key: "updateBulkActionButton",
      value: function updateBulkActionButton() {
        var count = document.querySelectorAll('.rtbcb-lead-checkbox:checked').length;
        var button = document.querySelector('#rtbcb-bulk-form button[type="submit"]');
        if (button) {
          button.disabled = count === 0;
        }
      }
    }, {
      key: "handleBulkAction",
      value: function handleBulkAction(e) {
        try {
          e.preventDefault();
          var action = document.getElementById('rtbcb-bulk-action').value;
          var ids = Array.from(document.querySelectorAll('.rtbcb-lead-checkbox:checked')).map(function (cb) {
            return cb.value;
          });
          if (!action || ids.length === 0) {
            return _await();
          }
          if (action === 'delete' && !confirm(rtbcbAdmin.strings.confirm_bulk_delete)) {
            return _await();
          }
          return _await(_catch(function () {
            var formData = new FormData();
            formData.append('action', 'rtbcb_bulk_action_leads');
            formData.append('nonce', rtbcbAdmin.nonce);
            formData.append('bulk_action', action);
            formData.append('lead_ids', JSON.stringify(ids));
            return _await(fetch(rtbcbAdmin.ajax_url, {
              method: 'POST',
              body: formData
            }), function (response) {
              if (!response.ok) {
                throw new Error("Server responded ".concat(response.status));
              }
              return _await(response.json(), function (data) {
                if (data.success) {
                  location.reload();
                } else {
                  var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                  alert(message);
                }
              });
            });
          }, function (err) {
            alert("".concat(rtbcbAdmin.strings.error, " ").concat(err.message));
          }));
        } catch (e) {
          return Promise.reject(e);
        }
      }
    }, {
      key: "viewLeadDetails",
      value: function viewLeadDetails(e) {
        try {
          e.preventDefault();
          var row = e.currentTarget.closest('tr');
          var email = row.querySelector('.column-email strong').textContent;
          var companySize = row.querySelector('.column-company-size').textContent.trim();
          var category = row.querySelector('.column-category').textContent.trim();
          var roi = row.querySelector('.column-roi').textContent.trim();
          var date = row.querySelector('.column-date').textContent.trim();
          var detailsHtml = "\n                <div class=\"rtbcb-lead-detail-grid\">\n                    <div class=\"rtbcb-detail-item\"><label>Email:</label><span>".concat(email, "</span></div>\n                    <div class=\"rtbcb-detail-item\"><label>Company Size:</label><span>").concat(companySize, "</span></div>\n                    <div class=\"rtbcb-detail-item\"><label>Recommended Category:</label><span>").concat(category, "</span></div>\n                    <div class=\"rtbcb-detail-item\"><label>Base ROI:</label><span>").concat(roi, "</span></div>\n                    <div class=\"rtbcb-detail-item\"><label>Submitted:</label><span>").concat(date, "</span></div>\n                </div>");
          document.getElementById('rtbcb-lead-details').innerHTML = detailsHtml;
          document.getElementById('rtbcb-lead-modal').style.display = 'block';
          return _await();
        } catch (e) {
          return Promise.reject(e);
        }
      }
    }, {
      key: "deleteLead",
      value: function deleteLead(e) {
        try {
          var _this7 = this;
          e.preventDefault();
          if (!confirm(rtbcbAdmin.strings.confirm_delete)) {
            return _await();
          }
          var id = e.currentTarget.dataset.leadId;
          return _await(_catch(function () {
            var formData = new FormData();
            formData.append('action', 'rtbcb_delete_lead');
            formData.append('nonce', rtbcbAdmin.nonce);
            formData.append('lead_id', id);
            return _await(fetch(rtbcbAdmin.ajax_url, {
              method: 'POST',
              body: formData
            }), function (response) {
              if (!response.ok) {
                throw new Error("Server responded ".concat(response.status));
              }
              return _await(response.json(), function (data) {
                if (data.success) {
                  e.currentTarget.closest('tr').remove();
                  _this7.updateBulkActionButton();
                } else {
                  var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                  alert(message);
                }
              });
            });
          }, function (err) {
            alert("".concat(rtbcbAdmin.strings.error, " ").concat(err.message));
          }));
        } catch (e) {
          return Promise.reject(e);
        }
      }
    }]);
  }();
  $(function () {
    RTBCBAdmin.init();
  });
})(jQuery);

document.addEventListener('DOMContentLoaded', function () {
    var tabWrapper = document.getElementById('rtbcb-test-tabs');
    if (!tabWrapper) {
        return;
    }

    var tabs = Array.prototype.slice.call(tabWrapper.querySelectorAll('[role="tab"]'));

    function activateTab(tab) {
        var i;
        var panel;
        for (i = 0; i < tabs.length; i++) {
            tabs[i].classList.remove('nav-tab-active');
            tabs[i].setAttribute('aria-selected', 'false');
            panel = document.getElementById(tabs[i].getAttribute('aria-controls'));
            if (panel) {
                panel.style.display = 'none';
            }
        }
        tab.classList.add('nav-tab-active');
        tab.setAttribute('aria-selected', 'true');
        panel = document.getElementById(tab.getAttribute('aria-controls'));
        if (panel) {
            panel.style.display = 'block';
            panel.focus();
        }
    }

    tabWrapper.addEventListener('click', function (e) {
        if (e.target && e.target.getAttribute('role') === 'tab') {
            e.preventDefault();
            activateTab(e.target);
        }
    });

    tabWrapper.addEventListener('keydown', function (e) {
        var index = tabs.indexOf(document.activeElement);
        if (index === -1) {
            return;
        }

        var newIndex = null;
        if (e.key === 'ArrowRight') {
            newIndex = (index + 1) % tabs.length;
        } else if (e.key === 'ArrowLeft') {
            newIndex = (index - 1 + tabs.length) % tabs.length;
        } else if (e.key === 'Home') {
            newIndex = 0;
        } else if (e.key === 'End') {
            newIndex = tabs.length - 1;
        }

        if (newIndex !== null) {
            e.preventDefault();
            tabs[newIndex].focus();
            activateTab(tabs[newIndex]);
        }
    });
});

function rtbcb_set_ajaxurl() {
    if (typeof ajaxurl === 'undefined' && window.rtbcbAdmin && rtbcbAdmin.ajax_url) {
        window.ajaxurl = rtbcbAdmin.ajax_url;
    }
}

function rtbcb_bind_regenerate(triggerId, targetId, eventType) {
    var trigger = document.getElementById(triggerId);
    var target = document.getElementById(targetId);
    if (!trigger || !target) {
        return;
    }
    trigger.addEventListener('click', function () {
        if (eventType === 'submit') {
            if (typeof jQuery !== 'undefined') {
                jQuery(target).trigger('submit');
            } else {
                target.dispatchEvent(new Event('submit'));
            }
        } else {
            target.click();
        }
    });
}

function rtbcb_init_api_test() {
    if (typeof jQuery === 'undefined') {
        return;
    }
    var $ = jQuery;
    var $btn = $('#rtbcb-test-api-btn');
    if (!$btn.length) {
        return;
    }
    $btn.on('click', function () {
        var nonce = $btn.data('nonce');
        var testing = $btn.data('testing');
        var testingMsg = $btn.data('testing-msg');
        var success = $btn.data('success');
        var available = $btn.data('available');
        var http = $btn.data('http');
        var fail = $btn.data('fail');
        var ajaxFail = $btn.data('ajax-fail');
        var label = $btn.data('label');
        var $results = $('#rtbcb-test-results');
        $btn.prop('disabled', true).text(testing);
        $results.html('<p>' + testingMsg + '</p>');
        $.post(ajaxurl, {
            action: 'rtbcb_test_api',
            nonce: nonce
        }).done(function (response) {
            if (response.success) {
                var html = '<div class="notice notice-success"><p><strong>' + success + '</strong></p>' + '<p>' + response.data.details + '</p>';
                if (response.data.models_available) {
                    html += '<p><strong>' + available + '</strong> ' + response.data.models_available.join(', ') + '</p>';
                }
                html += '</div>';
                $results.html(html);
            } else {
                var errorHtml = '<div class="notice notice-error"><p><strong>❌ ' + response.data.message + '</strong></p>' + '<p>' + response.data.details + '</p>';
                if (response.data.http_code) {
                    errorHtml += '<p>' + http + ' ' + response.data.http_code + '</p>';
                }
                errorHtml += '</div>';
                $results.html(errorHtml);
            }
        }).fail(function () {
            $results.html('<div class="notice notice-error"><p><strong>❌ ' + fail + '</strong></p><p>' + ajaxFail + '</p></div>');
        }).always(function () {
            $btn.prop('disabled', false).text(label);
        });
    });
}

function rtbcb_init_connectivity_tests() {
    if (typeof jQuery === 'undefined') {
        return;
    }
    var $ = jQuery;
    var $status = $('#rtbcb-connectivity-status');
    if (!$status.length) {
        return;
    }
    function renderStatus(message, success) {
        var cls = success ? 'notice notice-success' : 'notice notice-error';
        $status.html('<div class="' + cls + '"><p>' + message + '</p></div>');
    }
    $('#rtbcb-test-openai').on('click', function () {
        var $btn = $(this);
        var original = $btn.text();
        var data = $btn.data();
        $btn.prop('disabled', true).text(rtbcbAdmin.strings.testing);
        $.post(ajaxurl, {
            action: 'rtbcb_test_api',
            nonce: data.nonce
        }).done(function (response) {
            if (response.success) {
                renderStatus(response.data.message || data.success, true);
            } else {
                renderStatus(response.data.message || data.failure, false);
            }
        }).fail(function () {
            renderStatus(data.requestFailed, false);
        }).always(function () {
            $btn.prop('disabled', false).text(original);
        });
    });
    $('#rtbcb-test-portal').on('click', function () {
        var $btn = $(this);
        var original = $btn.text();
        var data = $btn.data();
        $btn.prop('disabled', true).text(rtbcbAdmin.strings.testing);
        $.post(ajaxurl, {
            action: 'rtbcb_test_portal',
            nonce: data.nonce
        }).done(function (response) {
            if (response.success) {
                var msg = response.data && response.data.vendor_count !== undefined ? data.vendorLabel + ' ' + response.data.vendor_count : (response.data.message || data.success);
                renderStatus(msg, true);
            } else {
                var failMsg = response.data && response.data.message ? response.data.message : data.failure;
                renderStatus(failMsg, false);
            }
        }).fail(function () {
            renderStatus(data.requestFailed, false);
        }).always(function () {
            $btn.prop('disabled', false).text(original);
        });
    });
    $('#rtbcb-test-rag').on('click', function () {
        var $btn = $(this);
        var original = $btn.text();
        var data = $btn.data();
        $btn.prop('disabled', true).text(rtbcbAdmin.strings.testing);
        $.post(ajaxurl, {
            action: 'rtbcb_test_rag',
            nonce: data.nonce
        }).done(function (response) {
            if (response.success) {
                var ragMsg = response.data && response.data.status ? response.data.status : data.success;
                renderStatus(ragMsg, true);
            } else {
                var failMsg = response.data && response.data.message ? response.data.message : data.failure;
                renderStatus(failMsg, false);
            }
        }).fail(function () {
            renderStatus(data.requestFailed, false);
        }).always(function () {
            $btn.prop('disabled', false).text(original);
        });
    });
    $('#rtbcb-set-company').on('click', function () {
        var $btn = $(this);
        var original = $btn.text();
        var data = $btn.data();
        var name = $('#rtbcb-company-name').val();
        $btn.prop('disabled', true).text(data.saving);
        $.post(ajaxurl, {
            action: 'rtbcb_set_test_company',
            nonce: $('#rtbcb_set_test_company_nonce').val(),
            company_name: name
        }).done(function (response) {
            if (response.success) {
                renderStatus(response.data.message, true);
                $('#rtbcb-test-results-summary tbody').html('<tr><td colspan="5">' + data.noResults + '</td></tr>');
            } else {
                var failMsg = response.data && response.data.message ? response.data.message : data.requestFailed;
                renderStatus(failMsg, false);
            }
        }).fail(function () {
            renderStatus(data.requestFailed, false);
        }).always(function () {
            $btn.prop('disabled', false).text(original);
        });
    });
}

function rtbcb_sort_test_results(table, column) {
    var headers = table.querySelectorAll('th');
    var tbody = table.querySelector('tbody');
    var direction = headers[column].getAttribute('aria-sort') === 'ascending' ? 'descending' : 'ascending';
    headers.forEach(function (th) {
        th.setAttribute('aria-sort', 'none');
    });
    headers[column].setAttribute('aria-sort', direction);
    var rows = Array.from(tbody.querySelectorAll('tr')).filter(function (row) {
        return !row.classList.contains('rtbcb-no-results');
    });
    rows.sort(function (a, b) {
        var aText = a.children[column].textContent.trim().toLowerCase();
        var bText = b.children[column].textContent.trim().toLowerCase();
        if (direction === 'ascending') {
            return aText.localeCompare(bText);
        }
        return bText.localeCompare(aText);
    });
    rows.forEach(function (row) {
        tbody.appendChild(row);
    });
}

function rtbcb_init_test_results_table() {
    var table = document.getElementById('rtbcb-test-results-summary');
    if (!table) {
        return;
    }
    var search = document.getElementById('rtbcb-test-results-search');
    var headers = table.querySelectorAll('th');
    headers.forEach(function (th, index) {
        th.addEventListener('click', function () {
            rtbcb_sort_test_results(table, index);
        });
        th.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                rtbcb_sort_test_results(table, index);
            }
        });
    });
    if (search) {
        search.addEventListener('input', function () {
            var query = search.value.toLowerCase();
            var rows = Array.from(table.querySelectorAll('tbody tr')).filter(function (row) {
                return !row.classList.contains('rtbcb-no-results');
            });
            var visible = 0;
            rows.forEach(function (row) {
                var text = row.textContent.toLowerCase();
                var match = text.indexOf(query) !== -1;
                row.style.display = match ? '' : 'none';
                if (match) {
                    visible++;
                }
            });
            var noResults = table.querySelector('tbody .rtbcb-no-results');
            if (noResults) {
                noResults.style.display = visible === 0 ? '' : 'none';
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    rtbcb_set_ajaxurl();
    rtbcb_bind_regenerate('rtbcb-rerun-company-overview', 'rtbcb-generate-company-overview');
    rtbcb_bind_regenerate('rtbcb-rerun-real-treasury', 'rtbcb-generate-real-treasury-overview');
    rtbcb_bind_regenerate('rtbcb-rerun-treasury-tech', 'rtbcb-generate-treasury-tech-overview');
    rtbcb_bind_regenerate('rtbcb-rerun-report-preview', 'rtbcb-generate-report');
    rtbcb_bind_regenerate('rtbcb-rerun-benefits', 'rtbcb-benefits-estimate-form', 'submit');
    rtbcb_bind_regenerate('rtbcb-rerun-industry-overview', 'rtbcb-industry-overview-form', 'submit');
    rtbcb_bind_regenerate('rtbcb-rerun-category', 'rtbcb-generate-category-recommendation');
    rtbcb_bind_regenerate('rtbcb-rerun-report-test', 'rtbcb-generate-report');
    rtbcb_init_api_test();
    rtbcb_init_connectivity_tests();
    rtbcb_init_test_results_table();
});
