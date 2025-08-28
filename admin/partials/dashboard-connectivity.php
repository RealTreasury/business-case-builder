<?php
/**
 * Dashboard connectivity status and tests.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="card">
    <h2 class="title"><?php esc_html_e( 'Connectivity Tests & Status', 'rtbcb' ); ?></h2>
    <table class="widefat striped">
        <tbody>
            <tr>
                <th><?php esc_html_e( 'OpenAI API Key', 'rtbcb' ); ?></th>
                <td><?php echo $openai_status ? esc_html__( 'Configured', 'rtbcb' ) : esc_html__( 'Missing', 'rtbcb' ); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Portal Integration', 'rtbcb' ); ?></th>
                <td><?php echo $portal_active ? esc_html__( 'Active', 'rtbcb' ) : esc_html__( 'Inactive', 'rtbcb' ); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'RAG Index', 'rtbcb' ); ?></th>
                <td><?php echo $rag_health ? esc_html__( 'Healthy', 'rtbcb' ) : esc_html__( 'Needs attention', 'rtbcb' ); ?></td>
            </tr>
        </tbody>
    </table>
    <p class="submit">
        <button type="button" id="rtbcb-test-openai" class="button"><?php esc_html_e( 'Test OpenAI API', 'rtbcb' ); ?></button>
        <button type="button" id="rtbcb-test-portal" class="button"><?php esc_html_e( 'Test Portal Connection', 'rtbcb' ); ?></button>
        <button type="button" id="rtbcb-test-rag" class="button"><?php esc_html_e( 'Test RAG Index', 'rtbcb' ); ?></button>
    </p>
    <p id="rtbcb-connectivity-status"></p>

    <?php include RTBCB_DIR . 'admin/partials/dashboard-test-results.php'; ?>
</div>
<script>
(function($){
    function renderStatus($el, message, success){
        var cls = success ? 'notice notice-success' : 'notice notice-error';
        $el.html('<div class="' + cls + '"><p>' + message + '</p></div>');
    }

    $('#rtbcb-test-openai').on('click', function(){
        var $btn = $(this);
        var original = $btn.text();
        var $status = $('#rtbcb-connectivity-status');
        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Testing...', 'rtbcb' ) ); ?>');
        $.post(ajaxurl, {
            action: 'rtbcb_test_api',
            nonce: '<?php echo wp_create_nonce( 'rtbcb_test_api' ); ?>'
        }).done(function(response){
            if (response.success) {
                renderStatus($status, response.data.message || '<?php echo esc_js( __( 'Connection successful.', 'rtbcb' ) ); ?>', true);
            } else {
                renderStatus($status, response.data.message || '<?php echo esc_js( __( 'Connection failed.', 'rtbcb' ) ); ?>', false);
            }
        }).fail(function(){
            renderStatus($status, '<?php echo esc_js( __( 'Request failed.', 'rtbcb' ) ); ?>', false);
        }).always(function(){
            $btn.prop('disabled', false).text(original);
        });
    });

    $('#rtbcb-test-portal').on('click', function(){
        var $btn = $(this);
        var original = $btn.text();
        var $status = $('#rtbcb-connectivity-status');
        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Testing...', 'rtbcb' ) ); ?>');
        $.post(ajaxurl, {
            action: 'rtbcb_test_portal',
            nonce: '<?php echo wp_create_nonce( 'rtbcb_test_portal' ); ?>'
        }).done(function(response){
            if (response.success) {
                var msg = response.data && response.data.vendor_count !== undefined ? '<?php echo esc_js( __( 'Vendor count:', 'rtbcb' ) ); ?> ' + response.data.vendor_count : (response.data.message || '<?php echo esc_js( __( 'Portal test successful.', 'rtbcb' ) ); ?>');
                renderStatus($status, msg, true);
            } else {
                renderStatus($status, (response.data && response.data.message) ? response.data.message : '<?php echo esc_js( __( 'Test failed.', 'rtbcb' ) ); ?>', false);
            }
        }).fail(function(){
            renderStatus($status, '<?php echo esc_js( __( 'Request failed.', 'rtbcb' ) ); ?>', false);
        }).always(function(){
            $btn.prop('disabled', false).text(original);
        });
    });

    $('#rtbcb-test-rag').on('click', function(){
        var $btn = $(this);
        var original = $btn.text();
        var $status = $('#rtbcb-connectivity-status');
        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Testing...', 'rtbcb' ) ); ?>');
        $.post(ajaxurl, {
            action: 'rtbcb_test_rag',
            nonce: '<?php echo wp_create_nonce( 'rtbcb_test_rag' ); ?>'
        }).done(function(response){
            if (response.success) {
                var ragMsg = response.data && response.data.status ? response.data.status : '<?php echo esc_js( __( 'RAG index healthy.', 'rtbcb' ) ); ?>';
                renderStatus($status, ragMsg, true);
            } else {
                renderStatus($status, (response.data && response.data.message) ? response.data.message : '<?php echo esc_js( __( 'Test failed.', 'rtbcb' ) ); ?>', false);
            }
        }).fail(function(){
            renderStatus($status, '<?php echo esc_js( __( 'Request failed.', 'rtbcb' ) ); ?>', false);
        }).always(function(){
            $btn.prop('disabled', false).text(original);
        });
    });

    $('#rtbcb-set-company').on('click', function(){
        var $btn = $(this);
        var original = $btn.text();
        var $status = $('#rtbcb-connectivity-status');
        // Company name input resides in the Test Tools card; fetch safely.
        var nameInput = $('#rtbcb-company-name');
        var name = nameInput.length ? nameInput.val() : '';
        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Saving...', 'rtbcb' ) ); ?>');
        $.post(ajaxurl, {
            action: 'rtbcb_set_test_company',
            nonce: $('#rtbcb_set_test_company_nonce').val(),
            company_name: name
        }).done(function(response){
            if (response.success) {
                renderStatus($status, response.data.message, true);
                $('#rtbcb-test-results-summary tbody').html('<tr><td colspan="5"><?php echo esc_js( __( 'No test results found.', 'rtbcb' ) ); ?></td></tr>');
            } else {
                renderStatus($status, (response.data && response.data.message) ? response.data.message : '<?php echo esc_js( __( 'Request failed.', 'rtbcb' ) ); ?>', false);
            }
        }).fail(function(){
            renderStatus($status, '<?php echo esc_js( __( 'Request failed.', 'rtbcb' ) ); ?>', false);
        }).always(function(){
            $btn.prop('disabled', false).text(original);
        });
    });
})(jQuery);
</script>
