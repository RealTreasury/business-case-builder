/* Real Treasury Business Case Builder Admin JS */
jQuery(document).ready(function($) {
    'use strict';
    
    // Simple test - remove this once working
    console.log('RTBCB Admin JS loaded successfully');
    
    var RTBCB = window.RTBCB || {};
    window.RTBCB = RTBCB;
    
    RTBCB.Admin = {
        init: function() {
            this.bindEvents();
            this.initComponents();
        },
        
        bindEvents: function() {
            // Test API Connection
            $('#rtbcb-test-api').on('click', this.testApi);
            
            // Export Leads
            $('#rtbcb-export-leads, #rtbcb-export-data').on('click', this.exportLeads);
            
            // Rebuild Index
            $('#rtbcb-rebuild-index').on('click', this.rebuildIndex);
            
            // Run Diagnostics
            $('#rtbcb-run-tests').on('click', this.runDiagnostics);
            
            // Sync Local
            $('#rtbcb-sync-local').on('click', this.syncLocal);
            
            // Commentary Test
            $('#rtbcb-generate-commentary').on('click', this.testCommentary);
            
            // Company Overview Test
            $('#rtbcb-company-overview-form').on('submit', this.testCompanyOverview);
            
            // Industry Overview Test
            $('#rtbcb-industry-overview-form').on('submit', this.testIndustryOverview);

            // Benefits Test
            $('#rtbcb-benefits-estimate-form').on('submit', this.testBenefits);

            // Report Assembly Test
            $('#rtbcb-report-assembly-form').on('submit', this.testReportAssembly);

            // Test Dashboard
            $('#rtbcb-test-all-sections').on('click', this.runAllTests);
        },
        
        initComponents: function() {
            // Initialize leads manager if present
            if ($('#rtbcb-bulk-form').length) {
                this.initLeadsManager();
            }
            
            // Initialize tabs if present
            if ($('#rtbcb-test-tabs').length) {
                this.initTabs();
            }

            if ($('#rtbcb-test-all-sections').length) {
                $('#rtbcb-test-all-sections').addClass('rtbcb-primary-action').focus();
                if (window.rtbcbAdmin && window.rtbcbAdmin.auto_run_all) {
                    $('#rtbcb-test-all-sections').trigger('click');
                }
            }
        },
        
        testApi: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $label = $btn.find('h4');
            var original = $label.length ? $label.text() : $btn.text();
            
            ($label.length ? $label : $btn).text(window.rtbcbAdmin.strings.processing || 'Processing...');
            $btn.prop('disabled', true);
            
            $.ajax({
                url: window.rtbcbAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'rtbcb_test_connection',
                    nonce: window.rtbcbAdmin.nonce
                },
                async: false,
                success: function(response) {
                    var message = response.success ? 'API connection successful!' :
                        (response.data && response.data.message ? response.data.message : 'Connection failed');
                    alert(message);
                },
                error: function() {
                    alert('Request failed');
                },
                complete: function() {
                    ($label.length ? $label : $btn).text(original);
                    $btn.prop('disabled', false);
                }
            });
        },
        
        exportLeads: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $label = $btn.find('h4');
            var original = $label.length ? $label.text() : $btn.text();
            
            ($label.length ? $label : $btn).text(window.rtbcbAdmin.strings.processing || 'Processing...');
            $btn.prop('disabled', true);
            
            var params = new URLSearchParams(window.location.search);
            
            $.ajax({
                url: window.rtbcbAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'rtbcb_export_leads',
                    nonce: window.rtbcbAdmin.nonce,
                    search: params.get('search') || '',
                    category: params.get('category') || '',
                    date_from: params.get('date_from') || '',
                    date_to: params.get('date_to') || ''
                },
                async: false,
                success: function(response) {
                    if (response.success && response.data && response.data.content) {
                        var blob = new Blob([response.data.content], { type: 'text/csv' });
                        var url = URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = response.data.filename || 'leads.csv';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    } else {
                        alert(response.data && response.data.message ? response.data.message : 'Export failed');
                    }
                },
                error: function() {
                    alert('Export request failed');
                },
                complete: function() {
                    ($label.length ? $label : $btn).text(original);
                    $btn.prop('disabled', false);
                }
            });
        },
        
        rebuildIndex: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var original = $btn.text();
            
            $btn.text(window.rtbcbAdmin.strings.processing || 'Processing...').prop('disabled', true);
            
            $.ajax({
                url: window.rtbcbAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'rtbcb_rebuild_index',
                    nonce: window.rtbcbAdmin.nonce
                },
                async: false,
                success: function(response) {
                    if (response.success) {
                        alert('RAG index rebuilt successfully');
                        location.reload();
                    } else {
                        alert(response.data && response.data.message ? response.data.message : 'Rebuild failed');
                    }
                },
                error: function() {
                    alert('Rebuild request failed');
                },
                complete: function() {
                    $btn.text(original).prop('disabled', false);
                }
            });
        },
        
        runDiagnostics: function(e) {
            e.preventDefault();
            var $btn = $(this);
            $btn.prop('disabled', true);
            
            $.ajax({
                url: window.rtbcbAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'rtbcb_run_diagnostics',
                    nonce: $btn.data('nonce') || window.rtbcbAdmin.diagnostics_nonce
                },
                async: false,
                success: function(response) {
                    if (response.success) {
                        var message = '';
                        $.each(response.data, function(key, result) {
                            message += key + ': ' + (result.passed ? 'PASS' : 'FAIL') + ' - ' + result.message + '\n';
                        });
                        alert(message);
                    } else {
                        alert(response.data && response.data.message ? response.data.message : 'Diagnostics failed');
                    }
                },
                error: function() {
                    alert('Diagnostics request failed');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },
        
        syncLocal: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var original = $btn.text();
            
            $btn.text(window.rtbcbAdmin.strings.processing || 'Processing...').prop('disabled', true);
            
            var nonce = $('#rtbcb-sync-local-form').find('input[name="rtbcb_sync_local_nonce"]').val();
            
            $.ajax({
                url: window.rtbcbAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'rtbcb_sync_to_local',
                    nonce: nonce
                },
                async: false,
                success: function(response) {
                    alert(response.data && response.data.message ? response.data.message : 'Sync completed');
                },
                error: function() {
                    alert('Sync request failed');
                },
                complete: function() {
                    $btn.text(original).prop('disabled', false);
                }
            });
        },
        
        testCommentary: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var industry = $('#rtbcb-commentary-industry').val();
            var $results = $('#rtbcb-commentary-results');
            var original = $btn.text();
            
            $btn.prop('disabled', true).text(window.rtbcbAdmin.strings.generating || 'Generating...');
            
            $.ajax({
                url: window.rtbcbAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'rtbcb_test_commentary',
                    industry: industry,
                    nonce: window.rtbcbAdmin.company_overview_nonce
                },
                async: false,
                success: function(response) {
                    if (response.success && response.data) {
                        $results.text(response.data.overview || response.data.commentary || 'Generated successfully');
                    } else {
                        $results.text(response.data && response.data.message ? response.data.message : 'Generation failed');
                    }
                },
                error: function() {
                    $results.text('Request failed');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(original);
                }
            });
        },
        
        testCompanyOverview: function(e) {
            e.preventDefault();
            var $form = $(this);
            var $results = $('#rtbcb-company-overview-results');
            var $btn = $form.find('button[type="submit"]');
            var original = $btn.text();
            
            $btn.prop('disabled', true).text(window.rtbcbAdmin.strings.processing || 'Processing...');
            
            var company = $('#rtbcb-company-name').val();
            var nonce = window.rtbcbAdmin.company_overview_nonce;
            
            $.ajax({
                url: window.rtbcbAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'rtbcb_test_company_overview',
                    company_name: company,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        $results.html('<div class="notice notice-success"><p>' +
                            (response.data.overview || 'Generated successfully') + '</p></div>');

                        var $meta = $('#rtbcb-company-overview-meta');
                        if ($meta.length) {
                            var labels = window.rtbcbAdmin.strings || {};
                            $meta.empty();
                            if (response.data.word_count) {
                                $('<p/>').text((labels.word_count || 'Word Count') + ': ' + response.data.word_count).appendTo($meta);
                            }
                            if (response.data.elapsed) {
                                $('<p/>').text((labels.elapsed || 'Elapsed') + ': ' + response.data.elapsed + 's').appendTo($meta);
                            }
                            if (response.data.recommendations && response.data.recommendations.length) {
                                $('<p/>').text((labels.recommendations || 'Recommendations') + ':').appendTo($meta);
                                var $ul = $('<ul/>');
                                response.data.recommendations.forEach(function(rec) {
                                    $('<li/>').text(rec).appendTo($ul);
                                });
                                $meta.append($ul);
                            }
                            if (response.data.references && response.data.references.length) {
                                $('<p/>').text((labels.references || 'References') + ':').appendTo($meta);
                                var $ulRef = $('<ul/>');
                                response.data.references.forEach(function(ref) {
                                    $('<li/>').append(
                                        $('<a/>', {
                                            text: ref,
                                            href: ref,
                                            target: '_blank',
                                            rel: 'noopener noreferrer'
                                        })
                                    ).appendTo($ulRef);
                                });
                                $meta.append($ulRef);
                            }
                        }

                        if (response.data.metrics) {
                            window.rtbcbAdmin = window.rtbcbAdmin || {};
                            rtbcbAdmin.company = rtbcbAdmin.company || {};
                            rtbcbAdmin.company.revenue = response.data.metrics.revenue || 0;
                            rtbcbAdmin.company.staff_count = response.data.metrics.staff_count || 0;
                            rtbcbAdmin.company.efficiency = response.data.metrics.efficiency || 0;
                        }
                    } else {
                        $results.html('<div class="notice notice-error"><p>' +
                            (response.data && response.data.message ? response.data.message : 'Generation failed') + '</p></div>');
                    }
                },
                error: function() {
                    $results.html('<div class="notice notice-error"><p>Request failed</p></div>');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(original);
                }
            });
        },
        
        testIndustryOverview: function(e) {
            e.preventDefault();
            var $form = $(this);
            var $results = $('#rtbcb-industry-overview-results');
            var $btn = $form.find('button[type="submit"]');
            var original = $btn.text();
            
            $btn.prop('disabled', true).text(window.rtbcbAdmin.strings.processing || 'Processing...');
            
            var industry = $('#rtbcb-industry-name').val();
            var size = $('#rtbcb-company-size').val();
            var nonce = $form.find('[name="nonce"]').val() || window.rtbcbAdmin.industry_overview_nonce;

            var companyData = {
                industry: industry,
                size: size
            };
            
            $.ajax({
                url: window.rtbcbAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'rtbcb_test_industry_overview',
                    company_data: JSON.stringify(companyData),
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var $meta = $('#rtbcb-industry-overview-meta');
                        $results.empty();
                        $('<div class="notice notice-success" />')
                            .append($('<p/>').text(response.data.overview || 'Generated successfully'))
                            .appendTo($results);
                        if ($meta.length) {
                            var labels = window.rtbcbAdmin.strings || {};
                            $meta.empty();
                            if (response.data.word_count) {
                                $('<p/>').text((labels.word_count || 'Word Count') + ': ' + response.data.word_count).appendTo($meta);
                            }
                            if (response.data.elapsed) {
                                $('<p/>').text((labels.elapsed || 'Elapsed') + ': ' + response.data.elapsed + 's').appendTo($meta);
                            }
                            if (response.data.recommendations && response.data.recommendations.length) {
                                $('<p/>').text((labels.recommendations || 'Recommendations') + ':').appendTo($meta);
                                var $ul = $('<ul/>');
                                response.data.recommendations.forEach(function(rec) {
                                    $('<li/>').text(rec).appendTo($ul);
                                });
                                $meta.append($ul);
                            }
                            if (response.data.references && response.data.references.length) {
                                $('<p/>').text((labels.references || 'References') + ':').appendTo($meta);
                                var $ulRef = $('<ul/>');
                                response.data.references.forEach(function(ref) {
                                    $('<li/>').append(
                                        $('<a/>', {
                                            text: ref,
                                            href: ref,
                                            target: '_blank',
                                            rel: 'noopener noreferrer'
                                        })
                                    ).appendTo($ulRef);
                                });
                                $meta.append($ulRef);
                            }
                            $results.append($meta);
                        }
                    } else {
                        $results.html('<div class="notice notice-error"><p>' +
                            (response.data && response.data.message ? response.data.message : 'Generation failed') + '</p></div>');
                    }
                },
                error: function() {
                    $results.html('<div class="notice notice-error"><p>Request failed</p></div>');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(original);
                }
            });
        },
        
        testBenefits: function(e) {
            e.preventDefault();
            var $results = $('#rtbcb-benefits-estimate-results');
            
            $results.text(window.rtbcbAdmin.strings.processing || 'Processing...');
            
            var company = window.rtbcbAdmin.company || {};

            $.ajax({
                url: window.rtbcbAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'rtbcb_test_estimated_benefits',
                    company_data: {
                        revenue: company.revenue,
                        staff_count: company.staff_count,
                        efficiency: company.efficiency
                    },
                    recommended_category: $('#rtbcb-test-category').val(),
                    nonce: window.rtbcbAdmin.benefits_estimate_nonce
                },
                async: false,
                success: function(response) {
                    if (response.success && response.data) {
                        $results.text(JSON.stringify(response.data.estimate || response.data, null, 2));
                    } else {
                        $results.text(response.data && response.data.message ? response.data.message : 'Generation failed');
                    }
                },
                error: function() {
                    $results.text('Request failed');
                }
            });
        },

        testReportAssembly: function(e) {
            e.preventDefault();
            var $form = $(this);
            var $results = $('#rtbcb-report-assembly-results');
            var $btn = $form.find('button[type="submit"]');
            var original = $btn.text();

            $btn.prop('disabled', true).text(window.rtbcbAdmin.strings.processing || 'Processing...');

            $.ajax({
                url: window.rtbcbAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'rtbcb_test_report_assembly',
                    nonce: $form.find('[name="nonce"]').val() || window.rtbcbAdmin.report_assembly_nonce
                },
                async: false,
                success: function(response) {
                    if (response.success && response.data && response.data.summary) {
                        var notice = $('<div class="notice notice-success" />');
                        var pre = $('<pre />').text(JSON.stringify(response.data.summary, null, 2));
                        notice.append(pre);
                        $results.html(notice);
                    } else {
                        $results.html('<div class="notice notice-error"><p>' +
                            (response.data && response.data.message ? response.data.message : 'Generation failed') +
                            '</p></div>');
                    }
                },
                error: function() {
                    $results.html('<div class="notice notice-error"><p>Request failed</p></div>');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(original);
                }
            });
        },

        runAllTests: async function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $status = $('#rtbcb-test-status');
            var $progress = $('#rtbcb-test-progress');
            var original = $btn.text();
            var companyName = $('#rtbcb-company-name').val();
            var companyNameTests = ['rtbcb_test_company_overview'];
            $btn.prop('disabled', true).text(window.rtbcbAdmin.strings.testing || 'Testing...');
            $progress.val(0).removeClass('rtbcb-complete');
            $status.text('Running tests...');

            var companyData = {};
            try {
                var companyResponse = await $.ajax({
                    url: window.rtbcbAdmin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'rtbcb_get_company_data',
                        nonce: window.rtbcbAdmin.real_treasury_overview_nonce
                    }
                });
                if (companyResponse.success) {
                    companyData = companyResponse.data || {};
                    window.rtbcbAdmin.company = companyData;
                }
            } catch (error) {
                console.error('Failed to fetch company data', error);
            }

            var tests = [
                { action: 'rtbcb_test_company_overview', label: 'Company Overview', nonce: window.rtbcbAdmin.company_overview_nonce },
                { action: 'rtbcb_test_data_enrichment', label: 'Data Enrichment', nonce: $('#rtbcb_test_data_enrichment_nonce').val() },
                { action: 'rtbcb_test_data_storage', label: 'Data Storage', nonce: $('#rtbcb_test_data_storage_nonce').val() },
                { action: 'rtbcb_test_maturity_model', label: 'Maturity Model', nonce: window.rtbcbAdmin.maturity_model_nonce || $('#rtbcb_test_maturity_model_nonce').val() },
                { action: 'rtbcb_test_rag_market_analysis', label: 'RAG Market Analysis', nonce: window.rtbcbAdmin.rag_market_analysis_nonce || $('#rtbcb_test_rag_market_analysis_nonce').val() },
                { action: 'rtbcb_test_value_proposition', label: 'Value Proposition', nonce: window.rtbcbAdmin.value_proposition_nonce || $('#rtbcb_test_value_proposition_nonce').val() },
                { action: 'rtbcb_test_industry_overview', label: 'Industry Overview', nonce: window.rtbcbAdmin.industry_overview_nonce || $('#rtbcb_test_industry_overview_nonce').val() },
                { action: 'rtbcb_test_real_treasury_overview', label: 'Real Treasury Overview', nonce: window.rtbcbAdmin.real_treasury_overview_nonce || $('#rtbcb_test_real_treasury_overview_nonce').val() },
                { action: 'rtbcb_test_calculate_roi', label: 'ROI Calculator', nonce: window.rtbcbAdmin.roi_nonce },
                { action: 'rtbcb_test_estimated_benefits', label: 'Estimated Benefits', nonce: window.rtbcbAdmin.benefits_estimate_nonce || $('#rtbcb_test_estimated_benefits_nonce').val() },
                { action: 'rtbcb_test_report_assembly', label: 'Report Assembly & Delivery', nonce: window.rtbcbAdmin.report_assembly_nonce || $('#rtbcb_test_report_assembly_nonce').val() },
                { action: 'rtbcb_test_tracking_script', label: 'Tracking Scripts', nonce: window.rtbcbAdmin.tracking_script_nonce || $('#rtbcb_test_tracking_script_nonce').val() },
                { action: 'rtbcb_test_follow_up_email', label: 'Follow-up Emails', nonce: window.rtbcbAdmin.follow_up_email_nonce || $('#rtbcb_test_follow_up_email_nonce').val() }
            ];
            var results = [];
            var total = tests.length;
            $progress.attr('max', total);
            $status.text('Running tests... (0/' + total + ')');
            var companyDataTests = ['rtbcb_test_industry_overview', 'rtbcb_test_estimated_benefits', 'rtbcb_test_report_assembly'];
            for (var i = 0; i < tests.length; i++) {
                var test = tests[i];
                $status.text('Testing ' + test.label + ' (' + (i + 1) + '/' + total + ')');

                try {
                    var requestData = {
                        action: test.action,
                        nonce: test.nonce || window.rtbcbAdmin.test_dashboard_nonce
                    };

                    if (companyName && companyNameTests.indexOf(test.action) !== -1) {
                        requestData.company_name = companyName;
                    }

                    if (Object.keys(companyData).length && companyDataTests.indexOf(test.action) !== -1) {
                        if (test.action === 'rtbcb_test_estimated_benefits') {
                            requestData.company_data = {
                                revenue: companyData.revenue,
                                staff_count: companyData.staff_count,
                                efficiency: companyData.efficiency
                            };
                            if (companyData.recommended_category) {
                                requestData.recommended_category = companyData.recommended_category;
                            }
                        } else {
                            requestData.company_data = JSON.stringify(companyData);
                        }
                    }

                    var response = await $.ajax({
                        url: window.rtbcbAdmin.ajax_url,
                        method: 'POST',
                        data: requestData
                    });

                    results.push({
                        section: test.label,
                        status: response.success ? 'SUCCESS' : 'FAILED',
                        message: response.data && response.data.message ? response.data.message : '',
                        data: response.data ? response.data : null
                    });
                } catch (error) {
                    var errMsg = ( error && error.responseJSON && error.responseJSON.data && error.responseJSON.data.message )
                        ? error.responseJSON.data.message
                        : ( error && error.message ? error.message : 'Request failed' );
                    var errData = ( error && error.responseJSON && error.responseJSON.data ) ? error.responseJSON.data : null;
                    results.push({
                        section: test.label,
                        status: 'ERROR',
                        message: errMsg,
                        data: errData
                    });
                }

                var completed = i + 1;
                $progress.val(completed);
                var percent = Math.round((completed / total) * 100);
                $status.text('Completed ' + completed + ' of ' + total + ' (' + percent + '%)');
            }

            $progress.addClass('rtbcb-complete');
            $status.text('Tests completed (' + total + '/' + total + ')');
            $btn.prop('disabled', false).text(original);

            var message = 'Test Results:\n';
            for (var j = 0; j < results.length; j++) {
                message += results[j].section + ': ' + results[j].status + '\n';
            }
            alert(message);

            try {
                var saveResponse = await $.ajax({
                    url: window.rtbcbAdmin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'rtbcb_save_test_results',
                        nonce: window.rtbcbAdmin.test_dashboard_nonce,
                        results: JSON.stringify(results)
                    }
                });

                if (saveResponse.success) {
                    $('#rtbcb-test-results-summary').load(window.location.href + ' #rtbcb-test-results-summary > *');
                }
            } catch (error) {
                console.error('Failed to save test results', error);
            }

            $('#rtbcb-section-tests').slideDown();
        },
        
        initLeadsManager: function() {
            var self = this;
            
            // Select All functionality
            $('#rtbcb-select-all').on('change', function() {
                var checked = this.checked;
                $('.rtbcb-lead-checkbox').prop('checked', checked);
                self.updateBulkButton();
            });
            
            // Individual checkbox changes
            $('.rtbcb-lead-checkbox').on('change', function() {
                self.updateSelectAll();
                self.updateBulkButton();
            });
            
            // Bulk form submission
            $('#rtbcb-bulk-form').on('submit', function(e) {
                e.preventDefault();
                var action = $('#rtbcb-bulk-action').val();
                var ids = [];
                $('.rtbcb-lead-checkbox:checked').each(function() {
                    ids.push(this.value);
                });
                
                if (!action || !ids.length) return;
                
                if (action === 'delete' && !confirm(window.rtbcbAdmin.strings.confirm_bulk_delete || 'Are you sure?')) {
                    return;
                }
                
                $.ajax({
                    url: window.rtbcbAdmin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'rtbcb_bulk_action_leads',
                        nonce: window.rtbcbAdmin.nonce,
                        bulk_action: action,
                        lead_ids: JSON.stringify(ids)
                    },
                    async: false,
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data && response.data.message ? response.data.message : 'Bulk action failed');
                        }
                    },
                    error: function() {
                        alert('Bulk action request failed');
                    }
                });
            });
            
            // View lead details
            $('.rtbcb-view-lead').on('click', function(e) {
                e.preventDefault();
                var $row = $(this).closest('tr');
                var email = $row.find('.column-email strong').text();
                var size = $row.find('.column-company-size').text().trim();
                var category = $row.find('.column-category').text().trim();
                var roi = $row.find('.column-roi').text().trim();
                var date = $row.find('.column-date').text().trim();
                
                var html = `
                    <div class="rtbcb-lead-detail-grid">
                        <div class="rtbcb-detail-item"><label>Email:</label><span>${email}</span></div>
                        <div class="rtbcb-detail-item"><label>Company Size:</label><span>${size}</span></div>
                        <div class="rtbcb-detail-item"><label>Category:</label><span>${category}</span></div>
                        <div class="rtbcb-detail-item"><label>ROI:</label><span>${roi}</span></div>
                        <div class="rtbcb-detail-item"><label>Date:</label><span>${date}</span></div>
                    </div>`;
                
                $('#rtbcb-lead-details').html(html);
                $('#rtbcb-lead-modal').show();
            });
            
            // Delete individual lead
            $('.rtbcb-delete-lead').on('click', function(e) {
                e.preventDefault();
                if (!confirm(window.rtbcbAdmin.strings.confirm_delete || 'Are you sure?')) {
                    return;
                }
                
                var id = $(this).data('lead-id');
                var $row = $(this).closest('tr');
                
                $.ajax({
                    url: window.rtbcbAdmin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'rtbcb_delete_lead',
                        nonce: window.rtbcbAdmin.nonce,
                        lead_id: id
                    },
                    async: false,
                    success: function(response) {
                        if (response.success) {
                            $row.remove();
                            self.updateBulkButton();
                        } else {
                            alert(response.data && response.data.message ? response.data.message : 'Delete failed');
                        }
                    },
                    error: function() {
                        alert('Delete request failed');
                    }
                });
            });
            
            // Modal close
            $('.rtbcb-modal-close').on('click', function() {
                $('#rtbcb-lead-modal').hide();
            });
            
            $('#rtbcb-lead-modal').on('click', function(e) {
                if (e.target.id === 'rtbcb-lead-modal') {
                    $(this).hide();
                }
            });
        },
        
        updateSelectAll: function() {
            var $all = $('#rtbcb-select-all');
            var total = $('.rtbcb-lead-checkbox').length;
            var checked = $('.rtbcb-lead-checkbox:checked').length;
            
            $all.prop('checked', total === checked && total > 0);
            $all.prop('indeterminate', checked > 0 && checked < total);
        },
        
        updateBulkButton: function() {
            var count = $('.rtbcb-lead-checkbox:checked').length;
            $('#rtbcb-bulk-form button[type="submit"]').prop('disabled', count === 0);
        },
        
         initTabs: function() {
             function showTab(target) {
                 $('#rtbcb-test-tabs a').removeClass('nav-tab-active');
                 $('#rtbcb-test-tabs a[href="' + target + '"]').addClass('nav-tab-active');
                 $('.rtbcb-tab-panel').hide();
                 $(target).show();
             }
             $('#rtbcb-test-tabs a').on('click', function(e) {
                 e.preventDefault();
                 showTab($(this).attr('href'));
             });
             $('.rtbcb-jump-tab').on('click', function(e) {
                 e.preventDefault();
                 showTab($(this).attr('href'));
             });
             if (window.location.hash && $('#rtbcb-test-tabs a[href="' + window.location.hash + '"]').length) {
                 showTab(window.location.hash);
             }
         }

    };
    
    // Initialize when ready
    RTBCB.Admin.init();
    
    // Export for external use
    window.RTBCBAdmin = RTBCB.Admin;
});
