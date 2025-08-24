// WordPress admin script - needs IE11+ compatibility
// Convert all ES6+ features to ES5 syntax
// Ensure proper minification compatibility
"use strict";

function _typeof(o) {
  "@babel/helpers - typeof";return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) {
    return typeof o;
  } : function (o) {
    return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o;
  }, _typeof(o);
}
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
var _Pact = /*#__PURE__*/(function () {
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
})();
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
function _classCallCheck(a, n) {
  if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function");
}
function _defineProperties(e, r) {
  for (var t = 0; t < r.length; t++) {
    var o = r[t];o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o);
  }
}
function _createClass(e, r, t) {
  return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e;
}
function _toPropertyKey(t) {
  var i = _toPrimitive(t, "string");return "symbol" == _typeof(i) ? i : i + "";
}
function _toPrimitive(t, r) {
  if ("object" != _typeof(t) || !t) return t;var e = t[Symbol.toPrimitive];if (void 0 !== e) {
    var i = e.call(t, r || "default");if ("object" != _typeof(i)) return i;throw new TypeError("@@toPrimitive must return a primitive value.");
  }return ("string" === r ? String : Number)(t);
}
function _slicedToArray(r, e) {
  return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest();
}
function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}
function _unsupportedIterableToArray(r, a) {
  if (r) {
    if ("string" == typeof r) return _arrayLikeToArray(r, a);var t = ({}).toString.call(r).slice(8, -1);return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0;
  }
}
function _arrayLikeToArray(r, a) {
  (null == a || a > r.length) && (a = r.length);for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e];return n;
}
function _iterableToArrayLimit(r, l) {
  var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"];if (null != t) {
    var e,
        n,
        i,
        u,
        a = [],
        f = !0,
        o = !1;try {
      if ((i = (t = t.call(r)).next, 0 === l)) {
        if (Object(t) !== t) return;f = !1;
      } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0);
    } catch (r) {
      o = !0, n = r;
    } finally {
      try {
        if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return;
      } finally {
        if (o) throw n;
      }
    }return a;
  }
}
function _arrayWithHoles(r) {
  if (Array.isArray(r)) return r;
}
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
      if (!rtbcbAdmin || rtbcbAdmin.page !== 'rtbcb-calculations') {
        return;
      }
      var button = $('#rtbcb-generate-commentary');
      if (!button.length) {
        return;
      }
      var results = $('#rtbcb-commentary-results');
      button.on('click', _async(function (e) {
        var _exit = false;
        e.preventDefault();
        var industry = $('#rtbcb-commentary-industry').val();
        var nonce = rtbcbAdmin.company_overview_nonce;
        var original = button.text();
        button.prop('disabled', true).text(rtbcbAdmin.strings.generating);
        return _continue(_catch(function () {
          var formData = new FormData();
          formData.append('action', 'rtbcb_test_company_overview');
          formData.append('industry', industry);
          formData.append('nonce', nonce);
          return _await(fetch(rtbcbAdmin.ajax_url, {
            method: 'POST',
            body: formData
          }), function (response) {
            if (!response.ok) {
              throw new Error("Server responded ".concat(response.status));
            }
            return _await(response.json(), function (data) {
              return _invokeIgnored(function () {
                if (data.success) {
                  var overview = data.data.overview || '';
                  results.text(overview);
                  return _invokeIgnored(function () {
                    if (navigator.clipboard) {
                      return _continueIgnored(_catch(function () {
                        return _await(navigator.clipboard.writeText(overview), function () {
                          alert(rtbcbAdmin.strings.copied);
                        });
                      }, _empty));
                    }
                  });
                } else {
                  var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                  alert(message);
                }
              });
            });
          });
        }, function (err) {
          alert("".concat(rtbcbAdmin.strings.error, " ").concat(err.message));
        }), function (_result) {
          if (_exit) return _result;
          button.prop('disabled', false).text(original);
        });
      }));
    },
    bindCompanyOverviewTest: function bindCompanyOverviewTest() {
      if (!rtbcbAdmin || rtbcbAdmin.page !== 'rtbcb-test-company-overview') {
        return;
      }

      var form = document.getElementById('rtbcb-company-overview-form');
      if (!form) {
        return;
      }

      var results = document.getElementById('rtbcb-company-overview-results');
      var clearBtn = document.getElementById('rtbcb-clear-results');
      var submitBtn = form.querySelector('button[type="submit"]');

      var submitHandler = _async(function (e) {
        var _exit = false;
        e.preventDefault();

        var originalText = submitBtn.textContent;
        var companyName = document.getElementById('rtbcb-company-name').value.trim();

        if (!companyName) {
          alert('Please enter a company name.');
          return;
        }

        submitBtn.disabled = true;

        return _continue(_catch(function () {
          // Phase 1: Basic Info
          submitBtn.textContent = 'Phase 1: Gathering basic info...';
          results.innerHTML = '<div class="notice"><p>Phase 1: Gathering basic company information...</p></div>';
          return _await(RTBCBAdmin.gatherBasicCompanyInfo(companyName), function (basicInfo) {
            results.innerHTML = '<div class="notice notice-info"><p><strong>Phase 1 Complete:</strong> Found ' + basicInfo.company_name + ' in ' + basicInfo.industry + '</p></div>';
            // Phase 2: Detailed Analysis
            submitBtn.textContent = 'Phase 2: Analyzing details...';
            return _await(RTBCBAdmin.conductDetailedAnalysis(basicInfo), function (detailedAnalysis) {
                // Phase 3: Final Report
                submitBtn.textContent = 'Phase 3: Compiling report...';
                var finalReport = RTBCBAdmin.compileFinalReport(basicInfo, detailedAnalysis);
                // Display results
                $(results).html(
                  RTBCBAdmin.utils.buildResult(
                    finalReport.analysis,
                    performance.now(),
                    $(form),
                    {
                      word_count: finalReport.analysis.split(' ').length,
                      recommendations_count: finalReport.recommendations.length,
                      references_count: finalReport.references.length
                    }
                  )
                );

              // Add recommendations section
              if (finalReport.recommendations.length > 0) {
                var recommendationsHtml = '<div class="rtbcb-recommendations" style="margin-top: 20px;"><h4>Treasury Technology Recommendations:</h4><ul>' + finalReport.recommendations.map(function (rec) {
                  return '<li>' + rec + '</li>';
                }).join('') + '</ul></div>';
                results.querySelector('.rtbcb-results').insertAdjacentHTML('beforeend', recommendationsHtml);
              }
            });
          });
        }, function (error) {
          console.error('Company analysis failed:', error);
          results.innerHTML = '<div class="notice notice-error"><p><strong>Analysis failed:</strong> ' + error.message + '</p></div>';
        }), function (_result) {
          if (_exit) return _result;
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
        });
      });

      form.addEventListener('submit', submitHandler);
      RTBCBAdmin.utils.bindClear($(clearBtn), $(results));
    },

    gatherBasicCompanyInfo: _async(function gatherBasicCompanyInfo(companyName) {
      var prompt = "Extract basic company information for " + companyName + ". Return only valid JSON:\n\n{\n  \"company_name\": \"string\",\n  \"industry\": \"string\", \n  \"primary_business\": \"string\",\n  \"annual_revenue\": \"string\",\n  \"employee_count\": \"string\",\n  \"headquarters\": \"string\",\n  \"public_private\": \"string\",\n  \"major_markets\": [\"string\"],\n  \"key_business_segments\": [\"string\"]\n}\n\nUse \"Not available\" for missing data.";

      var formData = new FormData();
      formData.append('action', 'rtbcb_openai_request');
      formData.append('prompt', prompt);
      formData.append('max_tokens', '800');
      formData.append('temperature', '0.3');
      formData.append('nonce', rtbcbAdmin.nonce);

      return _await(fetch(rtbcbAdmin.ajax_url, {
        method: 'POST',
        body: formData
      }), function (response) {
        if (!response.ok) {
          throw new Error('Phase 1 API call failed: ' + response.status);
        }
        return _await(response.json(), function (data) {
          if (!data.success) {
            throw new Error(data.data.message || 'Phase 1 failed');
          }
          return JSON.parse(data.data.response);
        });
      });
    }),

    conductDetailedAnalysis: _async(function conductDetailedAnalysis(basicInfo) {
      var analysisSteps = {
        financial: 'Company: ' + basicInfo.company_name + ', Industry: ' + basicInfo.industry + '\n\nProvide financial treasury analysis. Return valid JSON:\n{\n  "cash_position": "string",\n  "debt_profile": "string", \n  "working_capital": "string",\n  "currency_exposure": "string"\n}',
        challenges: 'Company: ' + basicInfo.company_name + ', Industry: ' + basicInfo.industry + '\n\nIdentify treasury challenges. Return valid JSON:\n{\n  "primary_challenges": ["string"],\n  "risk_factors": ["string"],\n  "compliance_requirements": ["string"]\n}',
        technology: 'Company: ' + basicInfo.company_name + ', Industry: ' + basicInfo.industry + '\n\nSuggest treasury technology solutions. Return valid JSON:\n{\n  "immediate_wins": ["string"],\n  "strategic_initiatives": ["string"],\n  "implementation_priorities": ["string"]\n}'
      };

      var results = {};

      return _await(_forOf(Object.keys(analysisSteps), function (key) {
        var prompt = analysisSteps[key];
        return _continue(_catch(function () {
          var formData = new FormData();
          formData.append('action', 'rtbcb_openai_request');
          formData.append('prompt', prompt);
          formData.append('max_tokens', '600');
          formData.append('temperature', '0.4');
          formData.append('nonce', rtbcbAdmin.nonce);
          return _await(fetch(rtbcbAdmin.ajax_url, {
            method: 'POST',
            body: formData
          }), function (response) {
            if (response.ok) {
              return _await(response.json(), function (data) {
                if (data.success) {
                  results[key] = JSON.parse(data.data.response);
                }
              });
            }
          });
        }, function (error) {
          console.warn('Step ' + key + ' failed:', error);
          results[key] = { error: 'Failed to analyze ' + key };
        }), function () {
          return _await(new Promise(function (resolve) {
            return setTimeout(resolve, 500);
          }));
        });
      }), function () {
        return results;
      });
    }),

    compileFinalReport: function compileFinalReport(basicInfo, analysisResults) {
      var analysis = basicInfo.company_name + ' is a ' + basicInfo.public_private + ' company in the ' + basicInfo.industry + ' industry';

      if (basicInfo.annual_revenue !== 'Not available') {
        analysis += ' with ' + basicInfo.annual_revenue + ' in annual revenue';
      }

      if (basicInfo.employee_count !== 'Not available') {
        analysis += ' and approximately ' + basicInfo.employee_count + ' employees';
      }

      analysis += '. The company operates primarily in ' + basicInfo.primary_business;

      if (basicInfo.key_business_segments.length > 0 && basicInfo.key_business_segments[0] !== 'Not available') {
        analysis += ' with key business segments including ' + basicInfo.key_business_segments.join(', ');
      }

      analysis += '. ';

      if (analysisResults.financial && !analysisResults.financial.error) {
        var fin = analysisResults.financial;
        analysis += 'From a treasury perspective, the company maintains ' + fin.cash_position + ' with ' + fin.debt_profile + '. ';
        analysis += 'Working capital management shows ' + fin.working_capital + '. ';
        if (fin.currency_exposure) {
          analysis += 'Currency exposure includes ' + fin.currency_exposure + '. ';
        }
      }

      if (analysisResults.challenges && !analysisResults.challenges.error) {
        var challenges = analysisResults.challenges;
        if (challenges.primary_challenges.length > 0) {
          analysis += 'Primary treasury challenges include ' + challenges.primary_challenges.join(', ') + '. ';
        }
      }

      var recommendations = [];
      if (analysisResults.technology && !analysisResults.technology.error) {
        var tech = analysisResults.technology;
        recommendations = recommendations.concat(tech.immediate_wins, tech.strategic_initiatives);
      }

      var references = [];
      if (basicInfo.public_private === 'Public') {
        references.push('SEC Edgar Database - ' + basicInfo.company_name);
        references.push(basicInfo.company_name + ' Investor Relations');
      }
      references.push(basicInfo.industry + ' Industry Analysis');

      return {
        analysis: analysis,
        recommendations: recommendations.filter(function (rec) {
          return rec && rec.length > 0;
        }),
        references: references
      };
    },
    bindIndustryOverviewTest: function bindIndustryOverviewTest() {
      if (!rtbcbAdmin || rtbcbAdmin.page !== 'rtbcb-test-industry-overview') {
        return;
      }
      var form = $('#rtbcb-industry-overview-form');
      if (!form.length) {
        return;
      }
      var results = $('#rtbcb-industry-overview-results');
      var clearBtn = $('#rtbcb-clear-results');
      var submitBtn = form.find('button[type="submit"]');
      var submitHandler = _async(function (e) {
        var _exit3 = false;
        e.preventDefault();
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
        return _continue(_catch(function () {
          var formData = new FormData();
          formData.append('action', 'rtbcb_test_industry_overview');
          formData.append('company_data', JSON.stringify(company));
          formData.append('nonce', nonce);
          return _await(fetch(rtbcbAdmin.ajax_url, {
            method: 'POST',
            body: formData
          }), function (response) {
            if (!response.ok) {
              throw new Error("Server responded ".concat(response.status));
            }
            return _await(response.json(), function (data) {
              if (data.success) {
                var text = data.data && data.data.overview ? data.data.overview : '';
                results.html(RTBCBAdmin.utils.buildResult(text, start, form, data.data));
              } else {
                var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                results.html('<div class="notice notice-error"><p>' + message + '</p></div>');
              }
            });
          });
        }, function (err) {
          results.html('<div class="notice notice-error"><p>' + rtbcbAdmin.strings.error + ' ' + err.message + '</p></div>');
        }), function (_result3) {
          if (_exit3) return _result3;
          RTBCBAdmin.utils.clearLoading(submitBtn, original);
        });
      });
      form.on('submit', submitHandler);
      RTBCBAdmin.utils.bindClear(clearBtn, results);
    },
    bindBenefitsEstimateTest: function bindBenefitsEstimateTest() {
      if (!rtbcbAdmin || rtbcbAdmin.page !== 'rtbcb-test-estimated-benefits') {
        return;
      }
      var form = $('#rtbcb-benefits-estimate-form');
      if (!form.length) {
        return;
      }
      var results = $('#rtbcb-benefits-estimate-results');
      form.on('submit', function (e) {
        e.preventDefault();
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
      });
    },
    bindTestDashboard: function bindTestDashboard() {
      var runTests = _async(function () {
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
        return _continue(_forOf(tests, function (test) {
          status.text('Testing ' + test.label + '...');
          return _continueIgnored(_catch(function () {
            return _await($.post(rtbcbAdmin.ajax_url, {
              action: test.action,
              nonce: test.nonce
            }), function (response) {
              var message = response && response.data && response.data.message ? response.data.message : '';
              results.push({
                section: test.label,
                status: response.success ? 'success' : 'error',
                message: message
              });
            });
          }, function (err) {
            results.push({
              section: test.label,
              status: 'error',
              message: err.message
            });
          }));
        }), function () {
          status.text('Saving results...');
          return _continue(_catch(function () {
            return _awaitIgnored($.post(rtbcbAdmin.ajax_url, {
              action: 'rtbcb_save_test_results',
              nonce: rtbcbAdmin.test_dashboard_nonce,
              results: JSON.stringify(results)
            }));
          }, _empty), function () {
            tableBody.empty();
            results.forEach(function (item) {
              var row = '<tr><td>' + item.section + '</td><td>' + item.status + '</td><td>' + item.message + '</td><td>' + new Date().toLocaleString() + '</td></tr>';
              tableBody.append(row);
            });
            status.text('');
            button.prop('disabled', false).text(originalText);
          });
        });
      });
      if (!rtbcbAdmin || rtbcbAdmin.page !== 'rtbcb-test-dashboard') {
        return;
      }
      var button = $('#rtbcb-test-all-sections');
      if (!button.length) {
        return;
      }
      var status = $('#rtbcb-test-status');
      var tableBody = $('#rtbcb-test-results-summary tbody');
      var originalText = button.text();
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
          alert(rtbcbAdmin.strings.error + ' ' + err.message);
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
  var RTBCBLeadsManager = /*#__PURE__*/(function () {
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
  })();
  $(function () {
    RTBCBAdmin.init();
  });
})(jQuery);
