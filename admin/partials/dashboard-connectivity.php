<?php
/**
 * Dashboard connectivity status and tests.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$company_data = get_option( 'rtbcb_company_data', [] );
$company_name = isset( $company_data['name'] ) ? sanitize_text_field( $company_data['name'] ) : '';
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
    <p>
        <label for="rtbcb-company-name"><?php esc_html_e( 'Company Name', 'rtbcb' ); ?></label>
        <input type="text" id="rtbcb-company-name" class="regular-text" value="<?php echo esc_attr( $company_name ); ?>" />
        <button type="button" id="rtbcb-set-company" class="button"><?php esc_html_e( 'Set Company', 'rtbcb' ); ?></button>
        <?php wp_nonce_field( 'rtbcb_set_company', 'rtbcb_set_company_nonce' ); ?>
    </p>
    <p id="rtbcb-connectivity-status"></p>

    <?php include RTBCB_DIR . 'admin/partials/dashboard-test-results.php'; ?>
</div>
<script>
(function($){
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
                $status.text(response.data.message || '<?php echo esc_js( __( 'Connection successful.', 'rtbcb' ) ); ?>');
            } else {
                $status.text(response.data.message || '<?php echo esc_js( __( 'Connection failed.', 'rtbcb' ) ); ?>');
            }
        }).fail(function(){
            $status.text('<?php echo esc_js( __( 'Request failed.', 'rtbcb' ) ); ?>');
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
            action: 'rtbcb_run_tests',
            nonce: '<?php echo wp_create_nonce( 'rtbcb_nonce' ); ?>'
        }).done(function(response){
            if (response.success && response.data.portal_integration) {
                $status.text(response.data.portal_integration.message);
            } else if (response.data && response.data.message) {
                $status.text(response.data.message);
            } else {
                $status.text('<?php echo esc_js( __( 'Test failed.', 'rtbcb' ) ); ?>');
            }
        }).fail(function(){
            $status.text('<?php echo esc_js( __( 'Request failed.', 'rtbcb' ) ); ?>');
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
            action: 'rtbcb_run_tests',
            nonce: '<?php echo wp_create_nonce( 'rtbcb_nonce' ); ?>'
        }).done(function(response){
            if (response.success && response.data.rag_index) {
                $status.text(response.data.rag_index.message);
            } else if (response.data && response.data.message) {
                $status.text(response.data.message);
            } else {
                $status.text('<?php echo esc_js( __( 'Test failed.', 'rtbcb' ) ); ?>');
            }
        }).fail(function(){
            $status.text('<?php echo esc_js( __( 'Request failed.', 'rtbcb' ) ); ?>');
        }).always(function(){
            $btn.prop('disabled', false).text(original);
        });
    });

    $('#rtbcb-set-company').on('click', function(){
        var $btn = $(this);
        var original = $btn.text();
        var $status = $('#rtbcb-connectivity-status');
        var name = $('#rtbcb-company-name').val();
        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Saving...', 'rtbcb' ) ); ?>');
        $.post(ajaxurl, {
            action: 'rtbcb_set_company_name',
            nonce: $('#rtbcb_set_company_nonce').val(),
            company_name: name
        }).done(function(response){
            if (response.success) {
                $status.text(response.data.message);
                $('#rtbcb-test-results-summary tbody').html('<tr><td colspan="4"><?php echo esc_js( __( 'No test results found.', 'rtbcb' ) ); ?></td></tr>');
            } else if (response.data && response.data.message) {
                $status.text(response.data.message);
            } else {
                $status.text('<?php echo esc_js( __( 'Request failed.', 'rtbcb' ) ); ?>');
            }
        }).fail(function(){
            $status.text('<?php echo esc_js( __( 'Request failed.', 'rtbcb' ) ); ?>');
        }).always(function(){
            $btn.prop('disabled', false).text(original);
        });
    });
})(jQuery);
</script>
