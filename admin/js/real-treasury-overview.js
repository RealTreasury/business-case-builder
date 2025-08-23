(function($) {
    'use strict';

    $(document).ready(function() {
        const $generateBtn = $('#rtbcb-generate-real-treasury-overview');
        const $clearBtn    = $('#rtbcb-clear-real-treasury-overview');
        const $results     = $('#rtbcb-real-treasury-overview-results');
        const nonce        = rtbcb_ajax.nonce;

        $generateBtn.on('click', function() {
            const includePortal = $('#rtbcb-include-portal').is(':checked') ? 1 : 0;
            const categories    = $('#rtbcb-vendor-categories').val() || [];

            $results.html('<p>Generating overview...</p>');

            $.ajax({
                url: rtbcb_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rtbcb_generate_real_treasury_overview',
                    include_portal: includePortal,
                    categories: categories,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success && response.data && response.data.overview) {
                        $results.html('<div class="rtbcb-result-text">' + response.data.overview + '</div>');
                    } else {
                        const message = (response.data && response.data.message) ? response.data.message : 'Error generating overview.';
                        $results.html('<div class="error"><p>' + message + '</p></div>');
                    }
                },
                error: function() {
                    $results.html('<div class="error"><p>Request failed. Please try again.</p></div>');
                }
            });
        });

        $clearBtn.on('click', function() {
            $results.empty();
            $('#rtbcb-include-portal').prop('checked', false);
            $('#rtbcb-vendor-categories').val([]);
        });
    });
})(jQuery);
