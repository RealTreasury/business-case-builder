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
            <tr>
                <th><?php esc_html_e( 'Last RAG Index', 'rtbcb' ); ?></th>
                <td>
                    <?php
                    if ( ! empty( $last_indexed ) ) {
                        echo esc_html( $last_indexed );
                    } else {
                        esc_html_e( 'Never', 'rtbcb' );
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Vendor Count', 'rtbcb' ); ?></th>
                <td><?php echo esc_html( intval( $vendor_count ) ); ?></td>
            </tr>
        </tbody>
    </table>
    <p class="submit">
        <button type="button" id="rtbcb-test-openai" class="button"><?php esc_html_e( 'Test OpenAI API', 'rtbcb' ); ?></button>
        <button type="button" id="rtbcb-test-portal" class="button"><?php esc_html_e( 'Test Portal Connection', 'rtbcb' ); ?></button>
        <button type="button" id="rtbcb-test-rag" class="button"><?php esc_html_e( 'Test RAG Index', 'rtbcb' ); ?></button>
        <button type="button" id="rtbcb-rebuild-rag" class="button"><?php esc_html_e( 'Rebuild RAG Index', 'rtbcb' ); ?></button>
    </p>
    <form id="rtbcb-sync-local-form" class="submit">
        <?php wp_nonce_field( 'rtbcb_sync_local', 'rtbcb_sync_local_nonce' ); ?>
        <button type="button" class="button" id="rtbcb-sync-to-local">
            <?php esc_html_e( 'Sync to Local', 'rtbcb' ); ?>
        </button>
    </form>
    <p id="rtbcb-connectivity-status"></p>

    <?php include RTBCB_DIR . 'admin/partials/dashboard-test-results.php'; ?>
</div>
<script>
(function($){
    function escHtml(text) {
        return $('<div />').text(text).html();
    }

    function renderStatus($el, message, success){
        var cls = success ? 'notice notice-success' : 'notice notice-error';
        $el.html('<div class="' + cls + '"><p>' + message + '</p></div>');
    }

    $('#rtbcb-test-openai').on('click', function(){
        var $btn = $(this);
        var original = $btn.text();
        var $status = $('#rtbcb-connectivity-status');
        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Testing...', 'rtbcb' ) ); ?>');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'rtbcb_test_api',
                nonce: '<?php echo wp_create_nonce( 'rtbcb_test_api' ); ?>'
            },
            async: false,
            success: function(response){
                if (response.success) {
                    renderStatus($status, response.data.message || '<?php echo esc_js( __( 'Connection successful.', 'rtbcb' ) ); ?>', true);
                } else {
                    renderStatus($status, response.data.message || '<?php echo esc_js( __( 'Connection failed.', 'rtbcb' ) ); ?>', false);
                }
            },
            error: function(){
                renderStatus($status, '<?php echo esc_js( __( 'Request failed.', 'rtbcb' ) ); ?>', false);
            },
            complete: function(){
                $btn.prop('disabled', false).text(original);
            }
        });
    });

    $('#rtbcb-test-portal').on('click', function(){
        var $btn = $(this);
        var original = $btn.text();
        var $status = $('#rtbcb-connectivity-status');
        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Testing...', 'rtbcb' ) ); ?>');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'rtbcb_test_portal',
                nonce: '<?php echo wp_create_nonce( 'rtbcb_test_portal' ); ?>'
            },
            async: false,
            success: function(response){
                if (response.success) {
                    var msg = response.data && response.data.vendor_count !== undefined ? '<?php echo esc_js( __( 'Vendor count:', 'rtbcb' ) ); ?> ' + response.data.vendor_count : (response.data.message || '<?php echo esc_js( __( 'Portal test successful.', 'rtbcb' ) ); ?>');
                    renderStatus($status, msg, true);
                } else {
                    renderStatus($status, (response.data && response.data.message) ? response.data.message : '<?php echo esc_js( __( 'Test failed.', 'rtbcb' ) ); ?>', false);
                }
            },
            error: function(){
                renderStatus($status, '<?php echo esc_js( __( 'Request failed.', 'rtbcb' ) ); ?>', false);
            },
            complete: function(){
                $btn.prop('disabled', false).text(original);
            }
        });
    });

    $('#rtbcb-test-rag').on('click', function(){
        var $btn = $(this);
        var original = $btn.text();
        var $status = $('#rtbcb-connectivity-status');
        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Testing...', 'rtbcb' ) ); ?>');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'rtbcb_test_rag',
                nonce: '<?php echo wp_create_nonce( 'rtbcb_test_rag' ); ?>'
            },
            success: function(response){
                if (response.success) {
                    var ragMsg = response.data && response.data.status ? escHtml(response.data.status) : '<?php echo esc_js( esc_html__( 'RAG index healthy.', 'rtbcb' ) ); ?>';

                    if (response.data) {
                        if (typeof response.data.indexed_items !== 'undefined') {
                            ragMsg += '<br><?php echo esc_js( esc_html__( 'Indexed items:', 'rtbcb' ) ); ?> ' + escHtml(response.data.indexed_items);
                        }

                        if (response.data.last_updated) {
                            ragMsg += '<br><?php echo esc_js( esc_html__( 'Last updated:', 'rtbcb' ) ); ?> ' + escHtml(response.data.last_updated);
                        }
                    }

                    renderStatus($status, ragMsg, true);
                } else {
                    var errMsg = (response.data && response.data.message) ? escHtml(response.data.message) : '<?php echo esc_js( esc_html__( 'Test failed.', 'rtbcb' ) ); ?>';
                    renderStatus($status, errMsg, false);
                }
            },
            error: function(){
                renderStatus($status, '<?php echo esc_js( esc_html__( 'Request failed.', 'rtbcb' ) ); ?>', false);
            },
            complete: function(){
                $btn.prop('disabled', false).text(original);
            }
        });
    });

    $('#rtbcb-rebuild-rag').on('click', function(){
        var $btn = $(this);
        var original = $btn.text();
        var $status = $('#rtbcb-connectivity-status');
        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Rebuilding...', 'rtbcb' ) ); ?>');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'rtbcb_rebuild_index',
                nonce: '<?php echo wp_create_nonce( 'rtbcb_nonce' ); ?>'
            },
            success: function(response){
                if (response.success) {
                    var msg = (response.data && response.data.message) ? escHtml(response.data.message) : '<?php echo esc_js( esc_html__( 'RAG index rebuilt successfully.', 'rtbcb' ) ); ?>';
                    renderStatus($status, msg, true);
                } else {
                    var err = (response.data && response.data.message) ? escHtml(response.data.message) : '<?php echo esc_js( esc_html__( 'Rebuild failed.', 'rtbcb' ) ); ?>';
                    renderStatus($status, err, false);
                }
            },
            error: function(){
                renderStatus($status, '<?php echo esc_js( esc_html__( 'Request failed.', 'rtbcb' ) ); ?>', false);
            },
            complete: function(){
                $btn.prop('disabled', false).text(original);
            }
        });
    });

    $('#rtbcb-set-company').on('click', function(){
        var $btn = $(this);
        var original = $btn.text();
        var $status = $('#rtbcb-connectivity-status');
        var $companyInput = $('#rtbcb-company-name');
        var name = $companyInput.val().trim();

        if (!name) {
            renderStatus($status, '<?php echo esc_js( __( 'Please enter a company name.', 'rtbcb' ) ); ?>', false);
            $companyInput.focus();
            return;
        }

        var $spinner = $('<span class="spinner is-active"></span>').insertAfter($btn);
        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Saving...', 'rtbcb' ) ); ?>');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            timeout: rtbcb_ajax.timeout,
            data: {
                action: 'rtbcb_set_test_company',
                nonce: $('#rtbcb_set_test_company_nonce').val(),
                company_name: name
            },
            success: function(response){
                if (response.success) {
                    renderStatus($status, escHtml(response.data.message), true);
                    $('#rtbcb-test-results-summary tbody').html('<tr><td colspan="7"><?php echo esc_js( __( 'No test results found.', 'rtbcb' ) ); ?></td></tr>');
                    generateOverview(name, original, $btn, $spinner, $status);
                } else {
                    renderStatus($status, (response.data && response.data.message) ? escHtml(response.data.message) : '<?php echo esc_js( __( 'Request failed.', 'rtbcb' ) ); ?>', false);
                    $btn.prop('disabled', false).text(original);
                    $spinner.remove();
                }
            },
            error: function(xhr, textStatus){
                var msg = '<?php echo esc_js( __( 'Request failed.', 'rtbcb' ) ); ?>';
                if (textStatus === 'timeout') {
                    msg = '<?php echo esc_js( __( 'Request timed out.', 'rtbcb' ) ); ?>';
                }
                renderStatus($status, msg, false);
                $btn.prop('disabled', false).text(original);
                $spinner.remove();
            }
        });
    });

    function generateOverview(name, original, $btn, $spinner, $status){
        $btn.text('<?php echo esc_js( __( 'Generating overview...', 'rtbcb' ) ); ?>');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            timeout: rtbcb_ajax.timeout,
            data: {
                action: 'rtbcb_generate_company_overview',
                nonce: '<?php echo wp_create_nonce( 'rtbcb_test_company_overview' ); ?>',
                company_name: name
            },
            success: function(response){
                if (response.success) {
                    renderStatus($status, '<?php echo esc_js( __( 'Company overview generated.', 'rtbcb' ) ); ?>', true);
                } else {
                    var msg = (response.data && response.data.message) ? escHtml(response.data.message) : '<?php echo esc_js( __( 'Overview generation failed.', 'rtbcb' ) ); ?>';
                    renderStatus($status, msg, false);
                }
            },
            error: function(xhr, textStatus){
                var msg = '<?php echo esc_js( __( 'Overview request failed.', 'rtbcb' ) ); ?>';
                if (textStatus === 'timeout') {
                    msg = '<?php echo esc_js( __( 'Overview request timed out.', 'rtbcb' ) ); ?>';
                }
                renderStatus($status, msg, false);
            },
            complete: function(){
                $btn.prop('disabled', false).text(original);
                $spinner.remove();
            }
        });
    }
})(jQuery);
</script>
