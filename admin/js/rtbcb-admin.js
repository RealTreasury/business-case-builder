/* Real Treasury Business Case Builder Admin JS */

// Ensure ajaxurl is defined for AJAX requests.
if ( typeof ajaxurl === 'undefined' ) {
    var ajaxurl = window.location.origin + '/wp-admin/admin-ajax.php';
}

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
            $('#rtbcb-sync-to-local').on('click', this.syncLocal);
            
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

            // Tracking Script Test
            $('#rtbcb-rerun-tracking-script').on('click', function() {
                $('#rtbcb-run-tracking-script').trigger('click');
            });
            $('#rtbcb-run-tracking-script').on('click', this.testTrackingScript);

            // Follow-up Email Test
            $('#rtbcb-rerun-follow-up').on('click', function() {
                $('#rtbcb-run-follow-up').trigger('click');
            });
            $('#rtbcb-run-follow-up').on('click', this.testFollowUpEmail);

            // Test Dashboard
            $('#rtbcb-test-all-sections').on('click', this.runAllTests);
            $('#rtbcb-regenerate-analysis').on('click', function(e){
                e.preventDefault();
                $('#rtbcb-test-all-sections').trigger('click');
            });
            $('#rtbcb-show-usage-map').on('click', function(){
                $('#rtbcb-usage-map-wrapper').toggle();
            });
            $('#rtbcb-export-analysis').on('click', function(){
                if (window.rtbcbAdmin.comprehensive) {
                    var txt = JSON.stringify(window.rtbcbAdmin.comprehensive, null, 2);
                    navigator.clipboard.writeText(txt);
                    alert('Results copied to clipboard');
                }
            });
            $('#rtbcb-clear-analysis').on('click', this.clearAnalysis);
            $('#rtbcb-start-new-analysis').on('click', this.startNewAnalysis);
            $('#rtbcb-company-name').on('change', function(){
                if (window.rtbcbAdmin.comprehensive) {
                    alert('Stored analysis may be outdated for new company.');
                }
            });
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

            if ($('#rtbcb-progress-steps').length) {
                this.initPhaseChart();
            }

            if ($('#rtbcb-test-all-sections').length) {
                $('#rtbcb-test-all-sections').addClass('rtbcb-primary-action').focus();
                if (window.rtbcbAdmin && window.rtbcbAdmin.auto_run_all) {
                    $('#rtbcb-test-all-sections').trigger('click');
                }
            }
        },
        
        testApi: async function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $label = $btn.find('h4');
            var original = $label.length ? $label.text() : $btn.text();

            ($label.length ? $label : $btn).text(window.rtbcbAdmin.strings.processing || 'Processing...');
            $btn.prop('disabled', true);

            try {
                var response = await $.ajax({
                    url: window.rtbcbAdmin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'rtbcb_test_connection',
                        nonce: window.rtbcbAdmin.nonce
                    }
                });
                var message = response.success ? 'API connection successful!' :
                    (response.data && response.data.message ? response.data.message : 'Connection failed');
                alert(message);
            } catch (error) {
                alert('Request failed');
            } finally {
                ($label.length ? $label : $btn).text(original);
                $btn.prop('disabled', false);
            }
        },
        
        exportLeads: async function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $label = $btn.find('h4');
            var original = $label.length ? $label.text() : $btn.text();

            ($label.length ? $label : $btn).text(window.rtbcbAdmin.strings.processing || 'Processing...');
            $btn.prop('disabled', true);

            var params = new URLSearchParams(window.location.search);

            try {
                var response = await $.ajax({
                    url: window.rtbcbAdmin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'rtbcb_export_leads',
                        nonce: window.rtbcbAdmin.nonce,
                        search: params.get('search') || '',
                        category: params.get('category') || '',
                        date_from: params.get('date_from') || '',
                        date_to: params.get('date_to') || ''
                    }
                });
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
            } catch (error) {
                alert('Export request failed');
            } finally {
                ($label.length ? $label : $btn).text(original);
                $btn.prop('disabled', false);
            }
        },
        
        rebuildIndex: async function(e) {
            e.preventDefault();
            var $btn = $(this);
            var original = $btn.text();

            $btn.text(window.rtbcbAdmin.strings.processing || 'Processing...').prop('disabled', true);

            try {
                var response = await $.ajax({
                    url: window.rtbcbAdmin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'rtbcb_rebuild_index',
                        nonce: window.rtbcbAdmin.nonce
                    }
                });
                if (response.success) {
                    alert('RAG index rebuilt successfully');
                    location.reload();
                } else {
                    alert(response.data && response.data.message ? response.data.message : 'Rebuild failed');
                }
            } catch (error) {
                alert('Rebuild request failed');
            } finally {
                $btn.text(original).prop('disabled', false);
            }
        },
        
        runDiagnostics: async function(e) {
            e.preventDefault();
            var $btn = $(this);
            $btn.prop('disabled', true);

            try {
                var response = await $.ajax({
                    url: window.rtbcbAdmin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'rtbcb_run_diagnostics',
                        nonce: $btn.data('nonce') || window.rtbcbAdmin.diagnostics_nonce
                    }
                });
                if (response.success) {
                    var message = '';
                    $.each(response.data, function(key, result) {
                        message += key + ': ' + (result.passed ? 'PASS' : 'FAIL') + ' - ' + result.message + '\n';
                    });
                    alert(message);
                } else {
                    alert(response.data && response.data.message ? response.data.message : 'Diagnostics failed');
                }
            } catch (error) {
                alert('Diagnostics request failed');
            } finally {
                $btn.prop('disabled', false);
            }
        },
        
        syncLocal: async function(e) {
            e.preventDefault();
            var $btn = $(this);
            var original = $btn.text();
            var $status = $('#rtbcb-connectivity-status');

            $btn.text(window.rtbcbAdmin.strings.processing || 'Processing...').prop('disabled', true);

            var nonce = $('#rtbcb-sync-local-form').find('input[name="rtbcb_sync_local_nonce"]').val();

            try {
                var response = await $.ajax({
                    url: window.rtbcbAdmin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'rtbcb_sync_to_local',
                        nonce: nonce
                    }
                });
                var message = response.data && response.data.message ? response.data.message : 'Sync completed';
                var cls = response.success ? 'notice notice-success' : 'notice notice-error';
                $status.html('<div class="' + cls + '"><p>' + message + '</p></div>');
            } catch (error) {
                var err = (window.rtbcbAdmin.strings && window.rtbcbAdmin.strings.error) ? window.rtbcbAdmin.strings.error : 'Sync request failed';
                $status.html('<div class="notice notice-error"><p>' + err + '</p></div>');
            } finally {
                $btn.text(original).prop('disabled', false);
            }
        },
        
        testCommentary: async function(e) {
            e.preventDefault();
            var $btn = $(this);
            var industry = $('#rtbcb-commentary-industry').val();
            var $results = $('#rtbcb-commentary-results');
            var original = $btn.text();

            $btn.prop('disabled', true).text(window.rtbcbAdmin.strings.generating || 'Generating...');

            try {
                var response = await $.ajax({
                    url: window.rtbcbAdmin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'rtbcb_test_commentary',
                        industry: industry,
                        nonce: window.rtbcbAdmin.company_overview_nonce
                    }
                });
                if (response.success && response.data) {
                    $results.text(response.data.overview || response.data.commentary || 'Generated successfully');
                } else {
                    $results.text(response.data && response.data.message ? response.data.message : 'Generation failed');
                }
            } catch (error) {
                $results.text('Request failed');
            } finally {
                $btn.prop('disabled', false).text(original);
            }
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
        
        testBenefits: async function(e) {
            e.preventDefault();
            var $results = $('#rtbcb-benefits-estimate-results');

            $results.text(window.rtbcbAdmin.strings.processing || 'Processing...');

            var company = window.rtbcbAdmin.company || {};

            try {
                var response = await $.ajax({
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
                    }
                });
                if (response.success && response.data) {
                    $results.text(JSON.stringify(response.data.estimate || response.data, null, 2));
                } else {
                    $results.text(response.data && response.data.message ? response.data.message : 'Generation failed');
                }
            } catch (error) {
                $results.text('Request failed');
            }
        },

        testReportAssembly: async function(e) {
            e.preventDefault();
            var $form = $(this);
            var $results = $('#rtbcb-report-assembly-results');
            var $btn = $form.find('button[type="submit"]');
            var original = $btn.text();

            $btn.prop('disabled', true).text(window.rtbcbAdmin.strings.processing || 'Processing...');

            try {
                var response = await $.ajax({
                    url: window.rtbcbAdmin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'rtbcb_test_report_assembly',
                        nonce: $form.find('[name="nonce"]').val() || window.rtbcbAdmin.report_assembly_nonce
                    }
                });
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
            } catch (error) {
                $results.html('<div class="notice notice-error"><p>Request failed</p></div>');
            } finally {
                $btn.prop('disabled', false).text(original);
            }
        },

        testTrackingScript: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var nonce = $('#rtbcb_test_tracking_script_nonce').val();
            var snippet = $('#rtbcb-tracking-snippet').val();
            var original = $btn.text();
            $btn.prop('disabled', true).text(window.rtbcbAdmin.strings.testing || 'Testing...');
            $('#rtbcb-tracking-script-result').html('');
            try {
                var script = document.createElement('script');
                script.text = snippet + "\n document.dispatchEvent(new CustomEvent('rtbcbTrackingEvent'));";
                document.body.appendChild(script);
            } catch (err) {
                $('#rtbcb-tracking-script-result').html('<div class="notice notice-error"><p>' + err.message + '</p></div>');
                $btn.prop('disabled', false).text(original);
                return;
            }
            $(document).one('rtbcbTrackingEvent', function() {
                $.ajax({
                    url: window.rtbcbAdmin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'rtbcb_test_tracking_script',
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#rtbcb-tracking-script-result').html('<div class="notice notice-success"><p>Event captured.</p></div>');
                        } else {
                            $('#rtbcb-tracking-script-result').html('<div class="notice notice-error"><p>' + (response.data && response.data.message ? response.data.message : 'Test failed.') + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#rtbcb-tracking-script-result').html('<div class="notice notice-error"><p>Request failed.</p></div>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text(original);
                    }
                });
            });
        },

        testFollowUpEmail: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var nonce = $('#rtbcb_test_follow_up_email_nonce').val();
            var original = $btn.text();
            $btn.prop('disabled', true).text(window.rtbcbAdmin.strings.testing || 'Testing...');
            $('#rtbcb-follow-up-result').html('');
            $.ajax({
                url: window.rtbcbAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'rtbcb_test_follow_up_email',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        var queue = response.data.queue || [];
                        $('#rtbcb-follow-up-result').html('<pre>' + JSON.stringify(queue, null, 2) + '</pre>');
                    } else {
                        $('#rtbcb-follow-up-result').html('<div class="notice notice-error"><p>' + (response.data && response.data.message ? response.data.message : 'Test failed.') + '</p></div>');
                    }
                },
                error: function() {
                    $('#rtbcb-follow-up-result').html('<div class="notice notice-error"><p>Request failed.</p></div>');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(original);
                }
            });
        },
        runAllTests: async function(e) {
            e.preventDefault();
            var $btn = $(this);
            var original = $btn.text();
            var companyName = $('#rtbcb-company-name').val().trim();
        
            if (!companyName) {
                alert(window.rtbcbAdmin.strings.company_required || 'Please enter a company name before running tests');
                return;
            }
        
            var $status = $('#rtbcb-test-status');
            var $progress = $('#rtbcb-test-progress');
            var $step = $('#rtbcb-test-step');
            var $config = $('#rtbcb-test-config');
        
            $btn.prop('disabled', true).text(window.rtbcbAdmin.strings.testing || 'Testing...');
            $progress.val(0).attr('aria-valuenow', 0).removeClass('rtbcb-complete');
            $status.text(window.rtbcbAdmin.strings.starting_tests || 'Starting tests...');
            $step.text('');
            $config.text('');
        
            var sections = (window.rtbcbAdmin.sections || []).filter(function(s) {
                return s.action;
            });
            var results = [];
        
            for (var i = 0; i < sections.length; i++) {
                var section = sections[i];
                $step.text(section.label);
                try {
                    var resp = await $.ajax({
                        url: window.rtbcbAdmin.ajax_url,
                        method: 'POST',
                        data: {
                            action: section.action,
                            nonce: section.nonce,
                            company_name: companyName
                        }
                    });
        
                    results.push({
                        section: section.id,
                        status: resp.success ? 'success' : 'error',
                        message: resp.data && resp.data.message ? resp.data.message : ''
                    });
        
                    try {
                        var cfg = await $.ajax({
                            url: window.rtbcbAdmin.ajax_url,
                            method: 'POST',
                            data: {
                                action: 'rtbcb_get_section_config',
                                nonce: window.rtbcbAdmin.test_dashboard_nonce,
                                section: section.id
                            }
                        });
                        if (cfg.success && cfg.data && cfg.data.config) {
                            $config.text(cfg.data.config);
                        } else {
                            $config.text('');
                        }
                    } catch (cfgErr) {
                        $config.text('');
                    }
        
                    $status.text('✅ ' + section.label);
                } catch (err) {
                    results.push({
                        section: section.id,
                        status: 'error',
                        message: err && err.message ? err.message : 'Request failed'
                    });
                    $status.text('❌ ' + section.label);
                }
        
                var pct = Math.round(((i + 1) / sections.length) * 100);
                $progress.val(pct).attr('aria-valuenow', pct);
                await RTBCB.Admin.refreshPhaseChart();
            }

            await RTBCB.Admin.saveTestResults(results);
            $progress.val(100).attr('aria-valuenow', 100).addClass('rtbcb-complete');
            $status.text('✅ ' + (window.rtbcbAdmin.strings.all_sections_done || 'All sections completed'));
            $('#rtbcb-section-tests').slideDown();
        
            $btn.prop('disabled', false).text(original);
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
            $('#rtbcb-bulk-form').on('submit', async function(e) {
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

                try {
                    var response = await $.ajax({
                        url: window.rtbcbAdmin.ajax_url,
                        method: 'POST',
                        data: {
                            action: 'rtbcb_bulk_action_leads',
                            nonce: window.rtbcbAdmin.nonce,
                            bulk_action: action,
                            lead_ids: JSON.stringify(ids)
                        }
                    });
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data && response.data.message ? response.data.message : 'Bulk action failed');
                    }
                } catch (error) {
                    alert('Bulk action request failed');
                }
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
            $('.rtbcb-delete-lead').on('click', async function(e) {
                e.preventDefault();
                if (!confirm(window.rtbcbAdmin.strings.confirm_delete || 'Are you sure?')) {
                    return;
                }

                var id = $(this).data('lead-id');
                var $row = $(this).closest('tr');

                try {
                    var response = await $.ajax({
                        url: window.rtbcbAdmin.ajax_url,
                        method: 'POST',
                        data: {
                            action: 'rtbcb_delete_lead',
                            nonce: window.rtbcbAdmin.nonce,
                            lead_id: id
                        }
                    });
                    if (response.success) {
                        $row.remove();
                        self.updateBulkButton();
                    } else {
                        alert(response.data && response.data.message ? response.data.message : 'Delete failed');
                    }
                } catch (error) {
                    alert('Delete request failed');
                }
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
                window.location.hash = target;
            }
            $('#rtbcb-test-tabs a').on('click', function(e) {
                e.preventDefault();
                showTab($(this).attr('href'));
            });
            $('.rtbcb-jump-tab').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                $('#rtbcb-section-tests').show();
                showTab(target);
                var section = document.getElementById('rtbcb-section-tests');
                if (section && section.scrollIntoView) {
                    section.scrollIntoView({ behavior: 'smooth' });
                }
            });
            if (window.location.hash && $('#rtbcb-test-tabs a[href="' + window.location.hash + '"]').length) {
                $('#rtbcb-section-tests').show();
                showTab(window.location.hash);
            }
        },

        initPhaseChart: function() {
            if (!window.rtbcbAdmin || !window.rtbcbAdmin.phaseKeys) {
                return;
            }
            var keys = window.rtbcbAdmin.phaseKeys;
            keys.forEach(function(k) {
                var pct = window.rtbcbAdmin.phaseCompletion[k] || 0;
                var $phase = $('.rtbcb-progress-phase[data-phase="' + k + '"]');
                $phase.find('.rtbcb-phase-percent').text(pct + '%');
                $phase.toggleClass('completed', pct >= 100);
            });

            $('.rtbcb-section-item').each(function() {
                var done = $(this).data('completed') === 1 || $(this).data('completed') === '1';
                $(this).toggleClass('completed', done);
                $(this).toggleClass('pending', !done);
            });

            $('.rtbcb-phase-toggle').off('click').on('click', function() {
                var $btn = $(this);
                var expanded = $btn.attr('aria-expanded') === 'true';
                $btn.attr('aria-expanded', expanded ? 'false' : 'true');
                $btn.closest('.rtbcb-progress-phase').toggleClass('expanded');
            });
        },

        refreshPhaseChart: async function() {
            try {
                var response = await $.ajax({
                    url: window.rtbcbAdmin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'rtbcb_get_phase_completion',
                        nonce: window.rtbcbAdmin.test_dashboard_nonce
                    }
                });
                if (response.success && response.data && response.data.percentages) {
                    window.rtbcbAdmin.phaseCompletion = response.data.percentages;
                    this.initPhaseChart();
                }
            } catch (error) {
                console.error('Failed to refresh progress', error);
            }
        },

        saveTestResults: async function(results) {
            try {
                var response = await $.ajax({
                    url: window.rtbcbAdmin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'rtbcb_save_test_results',
                        nonce: window.rtbcbAdmin.test_dashboard_nonce,
                        results: JSON.stringify(results)
                    }
                });
                if (response.success) {
                    console.log('Test results saved');
                } else {
                    var message = response.data && response.data.message ? response.data.message : (window.rtbcbAdmin.strings && window.rtbcbAdmin.strings.error ? window.rtbcbAdmin.strings.error : 'Failed to save test results');
                    var safeMessage = $('<div>').text(message).html();
                    $('#rtbcb-test-status').after('<div class="notice notice-error"><p>' + safeMessage + '</p></div>');
                    console.error(safeMessage);
                }
            } catch (error) {
                var message = (error.responseJSON && error.responseJSON.data && error.responseJSON.data.message) ? error.responseJSON.data.message : (window.rtbcbAdmin.strings && window.rtbcbAdmin.strings.error ? window.rtbcbAdmin.strings.error : 'Failed to save test results');
                var safeMessage = $('<div>').text(message).html();
                $('#rtbcb-test-status').after('<div class="notice notice-error"><p>' + safeMessage + '</p></div>');
                console.error('Failed to save test results', error);
            }
        },

        startNewAnalysis: async function(e) {
            e.preventDefault();
            var nonce = $('#rtbcb_clear_current_company_nonce').val();
            try {
                var response = await $.ajax({
                    url: window.rtbcbAdmin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'rtbcb_clear_current_company',
                        nonce: nonce
                    }
                });
                if (response.success) {
                    window.location.href = 'admin.php?page=rtbcb-test-dashboard#rtbcb-phase1';
                } else {
                    alert('Request failed');
                }
            } catch (error) {
                alert('Request failed');
            }
        },

        clearAnalysis: async function(e) {
            e.preventDefault();
            try {
                var response = await $.ajax({
                    url: window.rtbcbAdmin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'rtbcb_clear_analysis_data',
                        nonce: window.rtbcbAdmin.test_dashboard_nonce
                    }
                });
                if (response.success) {
                    $('#rtbcb-comprehensive-analysis').hide();
                    $('.rtbcb-view-source').hide();
                    $('.rtbcb-data-status').text('⚪ Generate new');
                }
            } catch (error) {
                alert('Clear failed');
            }
        }
    };
    
    // Initialize when ready
    RTBCB.Admin.init();

    // Horizontal scroll indicator for results table
    var $rtbcbResultsWrapper = $('.rtbcb-results-table-wrapper');
    function rtbcbUpdateScrollIndicator($el) {
        if ($el[0].scrollWidth > $el[0].clientWidth) {
            $el.addClass('rtbcb-scrollable');
            if ($el[0].scrollLeft + $el[0].clientWidth >= $el[0].scrollWidth - 1) {
                $el.addClass('rtbcb-scrolled-end');
            } else {
                $el.removeClass('rtbcb-scrolled-end');
            }
        } else {
            $el.removeClass('rtbcb-scrollable rtbcb-scrolled-end');
        }
    }
    $rtbcbResultsWrapper.each(function(){ rtbcbUpdateScrollIndicator($(this)); });
    $rtbcbResultsWrapper.on('scroll', function(){ rtbcbUpdateScrollIndicator($(this)); });
    $(window).on('resize', function(){
        $rtbcbResultsWrapper.each(function(){ rtbcbUpdateScrollIndicator($(this)); });
    });

    // Export for external use
    window.RTBCBAdmin = RTBCB.Admin;
});
