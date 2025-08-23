(function($) {
    'use strict';

    $(document).ready(function() {
        const generateBtn = $('#rtbcb-generate-real-treasury-overview');
        const clearBtn = $('#rtbcb-clear-real-treasury-overview');
        const resultsDiv = $('#rtbcb-real-treasury-overview-results');

        generateBtn.on('click', function() {
            const includePortal = $('#rtbcb-include-portal').is(':checked') ? 1 : 0;
            const categories = $('#rtbcb-vendor-categories').val() || [];
            const nonce = $('#rtbcb_test_real_treasury_overview_nonce').val();

            generateBtn.prop('disabled', true).text('Generating...');
            resultsDiv.html('<p>Generating overview...</p>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'rtbcb_test_real_treasury_overview',
                    include_portal: includePortal,
                    categories: categories,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        resultsDiv.html('<div class="notice notice-success"><p><strong>Overview:</strong></p><div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin-top: 10px;">' + response.data.overview + '</div></div>');
                    } else {
                        resultsDiv.html('<div class="notice notice-error"><p><strong>Error:</strong> ' + (response.data.message || 'Failed to generate overview') + '</p></div>');
                    }
                },
                error: function() {
                    resultsDiv.html('<div class="notice notice-error"><p><strong>Error:</strong> Request failed. Please try again.</p></div>');
                },
                complete: function() {
                    generateBtn.prop('disabled', false).text('Generate Real Treasury Overview');
                }
            });
        });

        clearBtn.on('click', function() {
            $('#rtbcb-include-portal').prop('checked', false);
            $('#rtbcb-vendor-categories').val([]);
            resultsDiv.html('');
        });
    });
})(jQuery);
