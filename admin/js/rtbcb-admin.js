// WordPress admin script - Compatible with modern browsers
(function($) {
    'use strict';

    var RTBCBAdmin = {
        utils: {
            setLoading: function(button, text) {
                var original = button.text();
                button.prop('disabled', true).text(text);
                return original;
            },
            clearLoading: function(button, original) {
                button.prop('disabled', false).text(original);
            },
            buildResult: function(text, start, form, meta) {
                meta = meta || {};
                var wordCount = meta.word_count || (text.trim() ? text.trim().split(/\s+/).length : 0);
                var duration = meta.elapsed || ((performance.now() - start) / 1000).toFixed(2);
                var timestamp = meta.generated ? new Date(meta.generated).toLocaleTimeString() : new Date().toLocaleTimeString();
                
                var container = $('<div class="rtbcb-results" />');
                container.append($('<p />').text(text));
                container.append($('<p class="rtbcb-result-meta" />').text(
                    'Word count: ' + wordCount + ' | Duration: ' + duration + 's | Time: ' + timestamp
                ));
                
                var actions = $('<p class="rtbcb-result-actions" />');
                var regen = $('<button type="button" class="button" />').text(rtbcbAdmin.strings.regenerate || 'Regenerate');
                var copy = $('<button type="button" class="button" />').text(rtbcbAdmin.strings.copy_text || 'Copy Text');
                
                regen.on('click', function() {
                    form.trigger('submit');
                });
                
                copy.on('click', function() {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(function() {
                            alert(rtbcbAdmin.strings.copied);
                        }).catch(function(err) {
                            alert(rtbcbAdmin.strings.error + ' ' + err.message);
                        });
                    }
                });
                
                actions.append(regen).append(' ').append(copy);
                container.append(actions);
                return container;
            },
            bindClear: function(clearBtn, results) {
                if (clearBtn.length) {
                    clearBtn.on('click', function() {
                        results.empty();
                    });
                }
            }
        },

        runCommentaryTest: function(e) {
            e.preventDefault();
            var button = $(e.currentTarget);
            var industry = $('#rtbcb-commentary-industry').val();
            var nonce = rtbcbAdmin.company_overview_nonce;
            var results = $('#rtbcb-commentary-results');
            var original = button.text();
            
            button.prop('disabled', true).text(rtbcbAdmin.strings.generating);
            
            $.post(rtbcbAdmin.ajax_url, {
                action: 'rtbcb_test_commentary',
                industry: industry,
                nonce: nonce
            }).done(function(data) {
                if (data.success) {
                    var overview = data.data.overview || '';
                    results.text(overview);
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(overview).catch(function() {});
                    }
                } else {
                    var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                    alert(message);
                }
            }).fail(function(xhr) {
                alert(rtbcbAdmin.strings.error + ' ' + xhr.statusText);
            }).always(function() {
                button.prop('disabled', false).text(original);
            });
        },

        runCompanyOverviewTest: function(e) {
            e.preventDefault();
            var form = $(e.currentTarget);
            var results = $('#rtbcb-company-overview-results');
            var submitBtn = form.find('button[type="submit"]');
            var original = RTBCBAdmin.utils.setLoading(submitBtn, rtbcbAdmin.strings.processing);
            
            var companyInput = $('#rtbcb-company-name');
            var company = companyInput.length ? companyInput.val() : '';
            var nonce = form.find('[name="nonce"]').val();
            var start = performance.now();
            
            $.post(rtbcbAdmin.ajax_url, {
                action: 'rtbcb_test_company_overview',
                company_name: company,
                nonce: nonce
            }).done(function(data) {
                if (data.success) {
                    var text = data.data && data.data.overview ? data.data.overview : '';
                    results.html(RTBCBAdmin.utils.buildResult(text, start, form, data.data));
                    
                    if (data.data) {
                        rtbcbAdmin.company = rtbcbAdmin.company || {};
                        if (data.data.focus_areas) {
                            rtbcbAdmin.company.focus_areas = data.data.focus_areas;
                        }
                        if (data.data.industry) {
                            rtbcbAdmin.company.industry = data.data.industry;
                        }
                        if (data.data.size) {
                            rtbcbAdmin.company.size = data.data.size;
                        }
                    }
                } else {
                    var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                    results.html('<div class="notice notice-error"><p>' + message + '</p></div>');
                }
            }).fail(function(xhr) {
                results.html('<div class="notice notice-error"><p>' + rtbcbAdmin.strings.error + ' ' + xhr.statusText + '</p></div>');
            }).always(function() {
                RTBCBAdmin.utils.clearLoading(submitBtn, original);
            });
        },

        runIndustryOverviewTest: function(e) {
            e.preventDefault();
            var form = $(e.currentTarget);
            var results = $('#rtbcb-industry-overview-results');
            var submitBtn = form.find('button[type="submit"]');
            var original = RTBCBAdmin.utils.setLoading(submitBtn, rtbcbAdmin.strings.processing);
            
            var company = $.extend({}, rtbcbAdmin.company || {});
            company.industry = $('#rtbcb-industry-name').val();
            var nonce = form.find('[name="nonce"]').val();
            
            if (!company.industry) {
                results.html('<div class="notice notice-error"><p>' + rtbcbAdmin.strings.error + '</p></div>');
                RTBCBAdmin.utils.clearLoading(submitBtn, original);
                return;
            }
            
            var start = performance.now();
            
            $.post(rtbcbAdmin.ajax_url, {
                action: 'rtbcb_test_industry_overview',
                company_data: JSON.stringify(company),
                nonce: nonce
            }).done(function(data) {
                if (data.success) {
                    var text = data.data && data.data.overview ? data.data.overview : '';
                    results.html(RTBCBAdmin.utils.buildResult(text, start, form, data.data));
                } else {
                    var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                    results.html('<div class="notice notice-error"><p>' + message + '</p></div>');
                }
            }).fail(function(xhr) {
                results.html('<div class="notice notice-error"><p>' + rtbcbAdmin.strings.error + ' ' + xhr.statusText + '</p></div>');
            }).always(function() {
                RTBCBAdmin.utils.clearLoading(submitBtn, original);
            });
        },

        runBenefitsEstimateTest: function(e) {
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
                recommended_category: rtbcbAdmin.company && rtbcbAdmin.company.recommended_category ? 
                    rtbcbAdmin.company.recommended_category : $('#rtbcb-test-category').val(),
                nonce: rtbcbAdmin.benefits_estimate_nonce
            };
            
            $.post(rtbcbAdmin.ajax_url, data).done(function(response) {
                if (response && response.success) {
                    results.text(JSON.stringify(response.data.estimate || response.data));
                } else {
                    var message = response && response.data && response.data.message ? 
                        response.data.message : rtbcbAdmin.strings.error;
                    results.text(message);
                }
            }).fail(function() {
                results.text(rtbcbAdmin.strings.error);
            });
        },

        init: function() {
            this.bindDashboardActions();
            this.bindExportButtons();
            this.initLeadsManager();
            this.bindDiagnosticsButton();
            this.bindSampleReport();
            this.bindSyncLocal();
            this.bindCommentaryTest();
            this.bindCompanyOverviewTest();
            this.bindIndustryOverviewTest();
            this.bindBenefitsEstimateTest();
            this.bindTestDashboard();
        },

        bindDashboardActions: function() {
            $('#rtbcb-test-api').on('click', this.testApiConnection);
            $('#rtbcb-rebuild-index').on('click', this.rebuildIndex);
            $('#rtbcb-export-data').on('click', this.exportLeads);
        },

        bindExportButtons: function() {
            $('#rtbcb-export-leads').on('click', this.exportLeads);
        },

        bindDiagnosticsButton: function() {
            $('#rtbcb-run-tests').on('click', this.runDiagnostics);
        },

        bindSyncLocal: function() {
            $('#rtbcb-sync-local').on('click', this.syncToLocal);
        },

        bindCommentaryTest: function() {
            var button = $('#rtbcb-generate-commentary');
            if (!button.length) {
                return;
            }
            button.on('click', RTBCBAdmin.runCommentaryTest);
        },

        bindCompanyOverviewTest: function() {
            var form = $('#rtbcb-company-overview-form');
            if (!form.length) {
                return;
            }
            var results = $('#rtbcb-company-overview-results');
            var clearBtn = $('#rtbcb-clear-results');
            RTBCBAdmin.utils.bindClear(clearBtn, results);
            form.on('submit', RTBCBAdmin.runCompanyOverviewTest);
        },

        bindIndustryOverviewTest: function() {
            var form = $('#rtbcb-industry-overview-form');
            if (!form.length) {
                return;
            }
            var results = $('#rtbcb-industry-overview-results');
            var clearBtn = $('#rtbcb-clear-results');
            RTBCBAdmin.utils.bindClear(clearBtn, results);
            form.on('submit', RTBCBAdmin.runIndustryOverviewTest);
        },

        bindBenefitsEstimateTest: function() {
            var form = $('#rtbcb-benefits-estimate-form');
            if (!form.length) {
                return;
            }
            form.on('submit', RTBCBAdmin.runBenefitsEstimateTest);
        },

        bindTestDashboard: function() {
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

            function runTests() {
                var tests = [
                    {
                        action: 'rtbcb_test_company_overview',
                        nonce: rtbcbAdmin.company_overview_nonce,
                        label: 'Company Overview'
                    },
                    {
                        action: 'rtbcb_test_treasury_tech_overview',
                        nonce: rtbcbAdmin.treasury_tech_overview_nonce,
                        label: 'Treasury Tech Overview'
                    },
                    {
                        action: 'rtbcb_test_industry_overview',
                        nonce: rtbcbAdmin.industry_overview_nonce,
                        label: 'Industry Overview'
                    }
                ];

                var results = [];
                var currentTest = 0;

                function runNextTest() {
                    if (currentTest >= tests.length) {
                        status.text('Saving results...');

                        $.post(rtbcbAdmin.ajax_url, {
                            action: 'rtbcb_save_test_results',
                            nonce: rtbcbAdmin.test_dashboard_nonce,
                            results: JSON.stringify(results)
                        }).always(function() {
                            tableBody.empty();
                            for (var i = 0; i < results.length; i++) {
                                var item = results[i];
                                var row = '<tr><td>' + item.section + '</td><td>' + item.status + '</td><td>' + 
                                    item.message + '</td><td>' + new Date().toLocaleString() + '</td></tr>';
                                tableBody.append(row);
                            }
                            status.text('');
                            button.prop('disabled', false).text(originalText);
                        });
                        return;
                    }

                    var test = tests[currentTest];
                    status.text('Testing ' + test.label + '...');

                    $.post(rtbcbAdmin.ajax_url, {
                        action: test.action,
                        nonce: test.nonce
                    }).done(function(response) {
                        var message = response && response.data && response.data.message ? response.data.message : '';
                        results.push({
                            section: test.label,
                            status: response.success ? 'success' : 'error',
                            message: message
                        });
                    }).fail(function(err) {
                        results.push({
                            section: test.label,
                            status: 'error',
                            message: err.statusText || 'Request failed'
                        });
                    }).always(function() {
                        currentTest++;
                        runNextTest();
                    });
                }

                runNextTest();
            }

            button.on('click', function() {
                button.prop('disabled', true).text(rtbcbAdmin.strings.testing);
                runTests();
            });
        },

        testApiConnection: function(e) {
            e.preventDefault();
            var button = $(this);
            var label = button.find('h4');
            var original = label.text();
            
            label.text(rtbcbAdmin.strings.processing);
            button.prop('disabled', true);
            
            $.post(rtbcbAdmin.ajax_url, {
                action: 'rtbcb_test_connection',
                nonce: rtbcbAdmin.nonce
            }).done(function(data) {
                var errMsg = data.data && data.data.message ? data.data.message : '';
                alert(data.success ? 'API connection successful!' : rtbcbAdmin.strings.error + errMsg);
            }).fail(function(xhr) {
                alert(rtbcbAdmin.strings.error + ' ' + xhr.statusText);
            }).always(function() {
                label.text(original);
                button.prop('disabled', false);
            });
        },

        rebuildIndex: function(e) {
            e.preventDefault();
            var button = $(this);
            var original = button.text();
            
            button.text(rtbcbAdmin.strings.processing).prop('disabled', true);
            
            $.post(rtbcbAdmin.ajax_url, {
                action: 'rtbcb_rebuild_index',
                nonce: rtbcbAdmin.nonce
            }).done(function(data) {
                if (data.success) {
                    alert('RAG index rebuilt successfully');
                    location.reload();
                } else {
                    var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                    alert(message);
                }
            }).fail(function(xhr) {
                alert(rtbcbAdmin.strings.error + ' ' + xhr.statusText);
            }).always(function() {
                button.text(original).prop('disabled', false);
            });
        },

        exportLeads: function(e) {
            e.preventDefault();
            var button = $(this);
            var label = button.find('h4').length ? button.find('h4') : button;
            var original = label.text();
            
            label.text(rtbcbAdmin.strings.processing);
            button.prop('disabled', true);
            
            var params = new URLSearchParams(window.location.search);
            
            $.post(rtbcbAdmin.ajax_url, {
                action: 'rtbcb_export_leads',
                nonce: rtbcbAdmin.nonce,
                search: params.get('search') || '',
                category: params.get('category') || '',
                date_from: params.get('date_from') || '',
                date_to: params.get('date_to') || ''
            }).done(function(data) {
                if (data.success) {
                    var blob = new Blob([data.data.content], { type: 'text/csv' });
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
            }).fail(function(xhr) {
                alert(rtbcbAdmin.strings.error + ' ' + xhr.statusText);
            }).always(function() {
                label.text(original);
                button.prop('disabled', false);
            });
        },

        runDiagnostics: function(e) {
            e.preventDefault();
            var button = $(this);
            button.prop('disabled', true);
            
            $.post(rtbcbAdmin.ajax_url, {
                action: 'rtbcb_run_diagnostics',
                nonce: $(this).data('nonce') || rtbcbAdmin.diagnostics_nonce
            }).done(function(data) {
                if (data.success) {
                    var message = '';
                    $.each(data.data, function(key, result) {
                        message += key + ': ' + (result.passed ? 'PASS' : 'FAIL') + ' - ' + result.message + '\n';
                    });
                    alert(message);
                    console.log('Diagnostics results:', data.data);
                } else {
                    var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                    alert(message);
                }
            }).fail(function(xhr) {
                console.error('Diagnostics error:', xhr);
                alert(rtbcbAdmin.strings.error + ' ' + xhr.statusText);
            }).always(function() {
                button.prop('disabled', false);
            });
        },

        syncToLocal: function(e) {
            e.preventDefault();
            var button = $(this);
            var original = button.text();
            
            button.text(rtbcbAdmin.strings.processing).prop('disabled', true);
            
            var nonce = $('#rtbcb-sync-local-form').find('input[name="rtbcb_sync_local_nonce"]').val();
            
            $.post(rtbcbAdmin.ajax_url, {
                action: 'rtbcb_sync_to_local',
                nonce: nonce
            }).done(function(data) {
                var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                alert(message);
            }).fail(function(xhr) {
                alert(rtbcbAdmin.strings.error + ' ' + xhr.statusText);
            }).always(function() {
                button.text(original).prop('disabled', false);
            });
        },

        initLeadsManager: function() {
            if (document.querySelector('#rtbcb-bulk-form')) {
                new RTBCBLeadsManager();
            }
        },

        bindReportPreview: function() {
            document.addEventListener('DOMContentLoaded', function() {
                var form = document.getElementById('rtbcb-report-preview-form');
                if (!form) {
                    return;
                }
                
                form.addEventListener('submit', function(e) {
                    RTBCBAdmin.generateReportPreview(e).catch(function(err) {
                        console.error(err);
                    });
                });
                
                var downloadBtn = document.getElementById('rtbcb-download-pdf');
                if (downloadBtn) {
                    downloadBtn.addEventListener('click', function(e) {
                        RTBCBAdmin.downloadReportPDF(e);
                    });
                }
                
                var select = document.getElementById('rtbcb-sample-select');
                if (select) {
                    var injectSample = function() {
                        var key = select.value;
                        var target = document.getElementById('rtbcb-sample-context');
                        if (key && target && rtbcbAdmin.sampleForms && rtbcbAdmin.sampleForms[key]) {
                            target.value = JSON.stringify(rtbcbAdmin.sampleForms[key], null, 2);
                        }
                    };
                    
                    select.addEventListener('change', function() {
                        injectSample();
                    });
                    
                    var loadSample = document.getElementById('rtbcb-load-sample');
                    if (loadSample) {
                        loadSample.addEventListener('click', function() {
                            injectSample();
                        });
                    }
                }
            });
        },

        generateReportPreview: function(e) {
            return new Promise(function(resolve, reject) {
                e.preventDefault();
                var form = e.currentTarget;
                var button = document.getElementById('rtbcb-generate-report');
                var original = button.textContent;
                
                button.textContent = rtbcbAdmin.strings.processing;
                button.disabled = true;
                
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
                
                fetch(rtbcbAdmin.ajax_url, {
                    method: 'POST',
                    body: formData
                }).then(function(response) {
                    if (!response.ok) {
                        return response.text().then(function(text) {
                            var error = new Error(text);
                            error.status = response.status;
                            error.requestDetails = {
                                action: action,
                                scenario_key: sampleKey
                            };
                            throw error;
                        });
                    }
                    return response.json();
                }).then(function(data) {
                    if (data.success) {
                        var iframe = document.getElementById('rtbcb-report-iframe');
                        if (iframe) {
                            iframe.srcdoc = data.data.html || data.data.report_html;
                        }
                        document.getElementById('rtbcb-report-preview-card').style.display = 'block';
                        document.getElementById('rtbcb-download-pdf').style.display = 'inline-block';
                        resolve(data);
                    } else {
                        var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                        alert(message);
                        reject(new Error(message));
                    }
                }).catch(function(err) {
                    if (err && err.status) {
                        console.error('generateReportPreview failed:', err.status, err.message, err.requestDetails);
                        alert(rtbcbAdmin.strings.error + ' ' + err.status + ': ' + err.message);
                    } else {
                        console.error('generateReportPreview exception:', err);
                        alert(rtbcbAdmin.strings.error + ' ' + (err && err.message ? err.message : ''));
                    }
                    reject(err);
                }).finally(function() {
                    button.textContent = original;
                    button.disabled = false;
                });
            });
        },

        bindSampleReport: function() {
            var button = document.getElementById('rtbcb-generate-sample-report');
            if (!button) {
                return;
            }
            button.addEventListener('click', this.generateSampleReport.bind(this));
        },

        generateSampleReport: function(e) {
            return new Promise(function(resolve, reject) {
                e.preventDefault();
                var button = e.currentTarget;
                var original = button.textContent;
                
                button.textContent = rtbcbAdmin.strings.processing;
                button.disabled = true;
                
                var formData = new FormData();
                var nonceField = document.getElementById('nonce');
                var nonce = nonceField ? nonceField.value : 
                    (rtbcbAdmin && rtbcbAdmin.report_preview_nonce ? rtbcbAdmin.report_preview_nonce : '');
                var action = 'rtbcb_generate_sample_report';
                
                formData.append('action', action);
                formData.append('nonce', nonce);
                
                fetch(rtbcbAdmin.ajax_url, {
                    method: 'POST',
                    body: formData
                }).then(function(response) {
                    if (!response.ok) {
                        return response.text().then(function(text) {
                            var requestDetails = {
                                action: action,
                                nonce: nonce
                            };
                            console.error('generateSampleReport failed:', response.status, text, requestDetails);
                            alert(rtbcbAdmin.strings.error + ' ' + response.status + ': ' + text);
                            throw new Error(text);
                        });
                    }
                    return response.json();
                }).then(function(data) {
                    if (data.success) {
                        var iframe = document.getElementById('rtbcb-sample-report-frame');
                        if (iframe) {
                            iframe.srcdoc = data.data.report_html;
                        }
                        resolve(data);
                    } else {
                        var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                        alert(message);
                        reject(new Error(message));
                    }
                }).catch(function(err) {
                    console.error('generateSampleReport exception:', err);
                    alert(rtbcbAdmin.strings.error + ' ' + err.message);
                    reject(err);
                }).finally(function() {
                    button.textContent = original;
                    button.disabled = false;
                });
            });
        },

        downloadReportPDF: function(e) {
            e.preventDefault();
            var iframe = document.getElementById('rtbcb-report-iframe');
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            }
        },

        closeModal: function() {
            var modal = document.getElementById('rtbcb-lead-modal');
            if (modal) {
                modal.style.display = 'none';
            }
        }
    };

    var RTBCBLeadsManager = function() {
        this.bindEvents();
        this.updateBulkActionButton();
    };

    RTBCBLeadsManager.prototype = {
        bindEvents: function() {
            var self = this;
            
            var selectAll = document.getElementById('rtbcb-select-all');
            if (selectAll) {
                selectAll.addEventListener('change', this.toggleSelectAll.bind(this));
            }
            
            var checkboxes = document.querySelectorAll('.rtbcb-lead-checkbox');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].addEventListener('change', this.updateSelectAll.bind(this));
                checkboxes[i].addEventListener('change', this.updateBulkActionButton.bind(this));
            }
            
            var bulkForm = document.getElementById('rtbcb-bulk-form');
            if (bulkForm) {
                bulkForm.addEventListener('submit', this.handleBulkAction.bind(this));
            }
            
            var viewButtons = document.querySelectorAll('.rtbcb-view-lead');
            for (var j = 0; j < viewButtons.length; j++) {
                viewButtons[j].addEventListener('click', this.viewLeadDetails.bind(this));
            }
            
            var deleteButtons = document.querySelectorAll('.rtbcb-delete-lead');
            for (var k = 0; k < deleteButtons.length; k++) {
                deleteButtons[k].addEventListener('click', this.deleteLead.bind(this));
            }
            
            var modalClose = document.querySelector('.rtbcb-modal-close');
            if (modalClose) {
                modalClose.addEventListener('click', RTBCBAdmin.closeModal.bind(RTBCBAdmin));
            }
            
            var leadModal = document.getElementById('rtbcb-lead-modal');
            if (leadModal) {
                leadModal.addEventListener('click', function(e) {
                    if (e.target.id === 'rtbcb-lead-modal') {
                        RTBCBAdmin.closeModal();
                    }
                });
            }
        },

        toggleSelectAll: function(e) {
            var checked = e.target.checked;
            var checkboxes = document.querySelectorAll('.rtbcb-lead-checkbox');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = checked;
            }
            this.updateBulkActionButton();
        },

        updateSelectAll: function() {
            var boxes = document.querySelectorAll('.rtbcb-lead-checkbox');
            var checked = document.querySelectorAll('.rtbcb-lead-checkbox:checked');
            var selectAll = document.getElementById('rtbcb-select-all');
            
            if (selectAll) {
                selectAll.checked = boxes.length === checked.length && boxes.length > 0;
                selectAll.indeterminate = checked.length > 0 && checked.length < boxes.length;
            }
        },

        updateBulkActionButton: function() {
            var count = document.querySelectorAll('.rtbcb-lead-checkbox:checked').length;
            var button = document.querySelector('#rtbcb-bulk-form button[type="submit"]');
            if (button) {
                button.disabled = count === 0;
            }
        },

        handleBulkAction: function(e) {
            var self = this;
            e.preventDefault();
            
            var action = document.getElementById('rtbcb-bulk-action').value;
            var checkedBoxes = document.querySelectorAll('.rtbcb-lead-checkbox:checked');
            var ids = [];
            
            for (var i = 0; i < checkedBoxes.length; i++) {
                ids.push(checkedBoxes[i].value);
            }
            
            if (!action || ids.length === 0) {
                return;
            }
            
            if (action === 'delete' && !confirm(rtbcbAdmin.strings.confirm_bulk_delete)) {
                return;
            }
            
            $.post(rtbcbAdmin.ajax_url, {
                action: 'rtbcb_bulk_action_leads',
                nonce: rtbcbAdmin.nonce,
                bulk_action: action,
                lead_ids: JSON.stringify(ids)
            }).done(function(data) {
                if (data.success) {
                    location.reload();
                } else {
                    var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                    alert(message);
                }
            }).fail(function(xhr) {
                alert(rtbcbAdmin.strings.error + ' ' + xhr.statusText);
            });
        },

        viewLeadDetails: function(e) {
            e.preventDefault();
            var row = e.currentTarget.closest('tr');
            var email = row.querySelector('.column-email strong').textContent;
            var companySize = row.querySelector('.column-company-size').textContent.trim();
            var category = row.querySelector('.column-category').textContent.trim();
            var roi = row.querySelector('.column-roi').textContent.trim();
            var date = row.querySelector('.column-date').textContent.trim();
            
            var detailsHtml = 
                '<div class="rtbcb-lead-detail-grid">' +
                    '<div class="rtbcb-detail-item"><label>Email:</label><span>' + email + '</span></div>' +
                    '<div class="rtbcb-detail-item"><label>Company Size:</label><span>' + companySize + '</span></div>' +
                    '<div class="rtbcb-detail-item"><label>Recommended Category:</label><span>' + category + '</span></div>' +
                    '<div class="rtbcb-detail-item"><label>Base ROI:</label><span>' + roi + '</span></div>' +
                    '<div class="rtbcb-detail-item"><label>Submitted:</label><span>' + date + '</span></div>' +
                '</div>';
            
            document.getElementById('rtbcb-lead-details').innerHTML = detailsHtml;
            document.getElementById('rtbcb-lead-modal').style.display = 'block';
        },

        deleteLead: function(e) {
            var self = this;
            e.preventDefault();
            
            if (!confirm(rtbcbAdmin.strings.confirm_delete)) {
                return;
            }
            
            var id = e.currentTarget.dataset.leadId;
            
            $.post(rtbcbAdmin.ajax_url, {
                action: 'rtbcb_delete_lead',
                nonce: rtbcbAdmin.nonce,
                lead_id: id
            }).done(function(data) {
                if (data.success) {
                    e.currentTarget.closest('tr').remove();
                    self.updateBulkActionButton();
                } else {
                    var message = data.data && data.data.message ? data.data.message : rtbcbAdmin.strings.error;
                    alert(message);
                }
            }).fail(function(xhr) {
                alert(rtbcbAdmin.strings.error + ' ' + xhr.statusText);
            });
        }
    };

    RTBCBAdmin.bindReportPreview();
    
    document.addEventListener('DOMContentLoaded', function() {
        RTBCBAdmin.init();
    });

})(jQuery);
