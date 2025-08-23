<?php
/**
 * Test Industry Overview admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Test Industry Overview', 'rtbcb' ); ?></h1>

    <div class="card">
        <h2 class="title"><?php esc_html_e( 'Generate Industry Overview', 'rtbcb' ); ?></h2>
        <p><?php esc_html_e( 'Enter an industry and company size to generate an AI-powered overview.', 'rtbcb' ); ?></p>

        <form id="rtbcb-industry-overview-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="rtbcb-industry-name"><?php esc_html_e( 'Industry Name', 'rtbcb' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="rtbcb-industry-name" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Manufacturing', 'rtbcb' ); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="rtbcb-company-size"><?php esc_html_e( 'Company Size', 'rtbcb' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="rtbcb-company-size" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Mid-size', 'rtbcb' ); ?>" />
                    </td>
                </tr>
            </table>
            <?php wp_nonce_field( 'rtbcb_test_industry_overview', 'nonce' ); ?>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Generate Overview', 'rtbcb' ); ?></button>
                <button type="button" id="rtbcb-clear-results" class="button"><?php esc_html_e( 'Clear Results', 'rtbcb' ); ?></button>
            </p>
        </form>
    </div>

    <div id="rtbcb-industry-overview-results"></div>
</div>

<style>
#rtbcb-industry-overview-results {
    margin-top: 20px;
}

#rtbcb-industry-overview-results .notice {
    margin: 5px 0;
}

#rtbcb-industry-overview-results div[style*="background"] {
    white-space: pre-wrap;
    line-height: 1.6;
}
</style>

<script>
<?php if ( ! isset( $GLOBALS['ajaxurl'] ) || empty( $GLOBALS['ajaxurl'] ) ) : ?>
var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
<?php endif; ?>
</script>
