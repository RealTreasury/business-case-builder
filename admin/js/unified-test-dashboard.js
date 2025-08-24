(function($){
    const Dashboard = {
        init() {
            $('.nav-tab').on('click', Dashboard.switchTab);
            $('.rtbcb-generate-overview').on('click', Dashboard.generateOverview);
            $('#rtbcb-clear-results').on('click', Dashboard.clearResults);
        },
        switchTab(e) {
            e.preventDefault();
            const tab = $(this).data('tab');
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            $('.rtbcb-test-section').hide();
            $('#' + tab).show();
        },
        generateOverview() {
            const company = $('#rtbcb-company-name').val();
            const model = $('#rtbcb-model').val();
            const debug = $('#rtbcb-debug').is(':checked');
            const data = {
                action: 'rtbcb_test_company_overview_enhanced',
                nonce: rtbcbDashboard.nonce,
                company_name: company,
                model: model,
                debug: debug ? 1 : 0
            };
            const $status = $('#rtbcb-overview-status');
            $status.text(rtbcbDashboard.strings.generating);
            $.post(rtbcbDashboard.ajaxurl, data)
                .done(function(resp){
                    if (resp.success) {
                        $('#rtbcb-overview-output').text(resp.data.overview);
                        $status.text(rtbcbDashboard.strings.complete);
                    } else {
                        $status.text(rtbcbDashboard.strings.error);
                    }
                })
                .fail(function(){
                    $status.text(rtbcbDashboard.strings.error);
                });
        },
        clearResults() {
            if (!window.confirm(rtbcbDashboard.strings.confirm_clear)) {
                return;
            }
            $('#rtbcb-overview-output').empty();
            $('#rtbcb-overview-status').empty();
        }
    };
    $(Dashboard.init);
})(jQuery);
