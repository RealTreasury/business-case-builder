(function($) {
    'use strict';

    $(document).ready(function() {
        const generateBtn = $('#rtbcb-generate-company-overview');
        const regenerateBtn = $('#rtbcb-regenerate-company-overview');
        const clearBtn = $('#rtbcb-clear-company-overview');
        const copyBtn = $('#rtbcb-copy-company-overview');
        const resultsDiv = $('#rtbcb-company-overview-results');
        const nonce = rtbcb_ajax.nonce;

        function generateCompanyOverview(companyName) {
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'rtbcb_start_company_overview',
                    company_name: companyName,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        pollJobStatus(response.data.job_id);
                        updateUI('processing', 'Analysis started. This may take several minutes...');
                    } else {
                        showError('Failed to start analysis: ' + (response.data.message || ''));
                    }
                },
                error: function() {
                    showError('Failed to start analysis. Please try again.');
                }
            });
        }

        function pollJobStatus(jobId) {
            const pollInterval = setInterval(function() {
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'rtbcb_check_job_status',
                        job_id: jobId,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            const job = response.data;
                            if (job.status === 'completed') {
                                clearInterval(pollInterval);
                                displayResults(job.result);
                                updateUI('completed', 'Analysis completed!');
                            } else if (job.status === 'failed') {
                                clearInterval(pollInterval);
                                showError('Analysis failed: ' + (job.error || 'Unknown error'));
                            } else {
                                updateUI('processing', job.progress || 'Processing...');
                            }
                        } else {
                            clearInterval(pollInterval);
                            showError('Job status check failed');
                        }
                    },
                    error: function() {
                        clearInterval(pollInterval);
                        showError('Connection error while checking status');
                    }
                });
            }, 3000);

            setTimeout(function() {
                clearInterval(pollInterval);
                showError('Analysis timed out. Please try again.');
            }, 600000);
        }

        function updateUI(status, message) {
            if (status === 'processing') {
                generateBtn.prop('disabled', true).text('Analyzing...');
                regenerateBtn.prop('disabled', true);
                copyBtn.hide();
                resultsDiv.html('<div class="notice notice-info"><p>' + message + '</p></div>');
            } else if (status === 'completed') {
                generateBtn.prop('disabled', false).text('Generate Overview');
                regenerateBtn.prop('disabled', false).show();
                copyBtn.show();
                resultsDiv.prepend('<div class="notice notice-success"><p>' + message + '</p></div>');
            }
        }

        function showError(message) {
            resultsDiv.html('<div class="notice notice-error"><p>' + message + '</p></div>');
            generateBtn.prop('disabled', false).text('Generate Overview');
            regenerateBtn.prop('disabled', false).show();
            copyBtn.hide();
        }

        function displayResults(result) {
            let html = '<div class="company-overview-results">';
            html += '<h3>Company Analysis</h3>';
            html += '<div class="analysis">' + (result.analysis || '') + '</div>';

            if (Array.isArray(result.recommendations) && result.recommendations.length > 0) {
                html += '<h4>Recommendations</h4><ul>';
                result.recommendations.forEach(function(rec) {
                    html += '<li>' + rec + '</li>';
                });
                html += '</ul>';
            }

            if (Array.isArray(result.references) && result.references.length > 0) {
                html += '<h4>References</h4><ul>';
                result.references.forEach(function(ref) {
                    html += '<li><a href="' + ref + '" target="_blank">' + ref + '</a></li>';
                });
                html += '</ul>';
            }

            html += '</div>';
            resultsDiv.html(html);
        }

        generateBtn.on('click', function() {
            const companyName = $('#rtbcb-test-company-name').val().trim();
            if (!companyName) {
                alert('Please enter a company name.');
                return;
            }
            generateCompanyOverview(companyName);
        });

        regenerateBtn.on('click', function() {
            const companyName = $('#rtbcb-test-company-name').val().trim();
            if (!companyName) {
                alert('Please enter a company name.');
                return;
            }
            generateCompanyOverview(companyName);
        });

        clearBtn.on('click', function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'rtbcb_clear_current_company',
                    nonce: nonce
                },
                complete: function() {
                    resultsDiv.html('');
                    $('#rtbcb-test-company-name').val('');
                    regenerateBtn.hide().prop('disabled', false);
                    copyBtn.hide();
                }
            });
        });

        copyBtn.on('click', function() {
            const text = resultsDiv.find('.company-overview-results').text();
            rtbcbTestUtils.copyToClipboard(text).then(function() {
                copyBtn.text('Copied!').prop('disabled', true);
                setTimeout(function() {
                    copyBtn.text('Copy to Clipboard').prop('disabled', false);
                }, 2000);
            });
        });

        regenerateBtn.hide();
        copyBtn.hide();
    });
})(jQuery);
