<?php
/**
 * Test Company Overview admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$clear_nonce = wp_create_nonce( 'rtbcb_clear_current_company' );
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Test Company Overview', 'rtbcb' ); ?></h1>
    <p>
        <button type="button" id="rtbcb-start-new-analysis" class="button">
            <?php esc_html_e( 'Start New Company Analysis', 'rtbcb' ); ?>
        </button>
    </p>
    
    <div class="card">
        <h2 class="title"><?php esc_html_e( 'Generate Company Overview', 'rtbcb' ); ?></h2>
        <p><?php esc_html_e( 'Enter a company name to generate an AI-powered overview using the configured LLM.', 'rtbcb' ); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="rtbcb-test-company-name">
                        <?php esc_html_e( 'Company Name', 'rtbcb' ); ?>
                    </label>
                </th>
                <td>
                    <input type="text" 
                           id="rtbcb-test-company-name" 
                           class="regular-text" 
                           placeholder="<?php esc_attr_e( 'Enter company name...', 'rtbcb' ); ?>" />
                    <?php wp_nonce_field( 'rtbcb_test_company_overview', 'rtbcb_test_company_overview_nonce' ); ?>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="button" id="rtbcb-generate-company-overview" class="button button-primary">
                <?php esc_html_e( 'Generate Overview', 'rtbcb' ); ?>
            </button>
            <button type="button" id="rtbcb-regenerate-company-overview" class="button" style="display:none;">
                <?php esc_html_e( 'Regenerate', 'rtbcb' ); ?>
            </button>
            <button type="button" id="rtbcb-copy-company-overview" class="button" style="display:none;">
                <?php esc_html_e( 'Copy to Clipboard', 'rtbcb' ); ?>
            </button>
            <button type="button" id="rtbcb-clear-company-overview" class="button">
                <?php esc_html_e( 'Clear', 'rtbcb' ); ?>
            </button>
        </p>
    </div>

    <div id="rtbcb-company-overview-results"></div>
    <div id="rtbcb-company-overview-meta"></div>
</div>

<style>
#rtbcb-company-overview-results {
    margin-top: 20px;
}

#rtbcb-company-overview-results .notice {
    margin: 5px 0;
}

#rtbcb-company-overview-results div[style*="background"] {
    white-space: pre-wrap;
    line-height: 1.6;
}

#rtbcb-company-overview-meta {
    margin-top: 10px;
}
</style>

<script>
// Ensure ajaxurl is available
<?php if ( ! isset( $GLOBALS['ajaxurl'] ) || empty( $GLOBALS['ajaxurl'] ) ) : ?>
var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
<?php endif; ?>
document.getElementById('rtbcb-start-new-analysis').addEventListener('click', function () {
    var data = new FormData();
    data.append('action', 'rtbcb_clear_current_company');
    data.append('nonce', '<?php echo esc_js( $clear_nonce ); ?>');
    fetch(ajaxurl, { method: 'POST', body: data })
        .then(function (response) { return response.json(); })
        .then(function () { location.reload(); });
});
</script>
