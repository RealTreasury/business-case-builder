<?php
/**
 * Test Real Treasury Overview admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Test Real Treasury Overview Generation', 'rtbcb' ); ?></h1>

    <div class="card">
        <h2 class="title"><?php esc_html_e( 'Generate Real Treasury Overview', 'rtbcb' ); ?></h2>
        <p><?php esc_html_e( 'Configure options and generate an overview.', 'rtbcb' ); ?></p>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="rtbcb-include-portal"><?php esc_html_e( 'Include Portal Data', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="rtbcb-include-portal" />
                        <?php esc_html_e( 'Include Portal Data', 'rtbcb' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb-vendor-categories"><?php esc_html_e( 'Vendor Categories', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <select id="rtbcb-vendor-categories" multiple size="5">
                        <option value="tms"><?php esc_html_e( 'TMS', 'rtbcb' ); ?></option>
                        <option value="cash_management"><?php esc_html_e( 'Cash Management', 'rtbcb' ); ?></option>
                        <option value="payments"><?php esc_html_e( 'Payments', 'rtbcb' ); ?></option>
                        <option value="liquidity"><?php esc_html_e( 'Liquidity', 'rtbcb' ); ?></option>
                        <option value="risk_management"><?php esc_html_e( 'Risk Management', 'rtbcb' ); ?></option>
                    </select>
                    <?php wp_nonce_field( 'rtbcb_test_real_treasury_overview', 'rtbcb_test_real_treasury_overview_nonce' ); ?>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="button" id="rtbcb-generate-real-treasury-overview" class="button button-primary">
                <?php esc_html_e( 'Generate Real Treasury Overview', 'rtbcb' ); ?>
            </button>
            <button type="button" id="rtbcb-clear-real-treasury-overview" class="button">
                <?php esc_html_e( 'Clear Results', 'rtbcb' ); ?>
            </button>
        </p>
    </div>

    <div id="rtbcb-real-treasury-overview-results"></div>
</div>

<style>
#rtbcb-real-treasury-overview-results {
    margin-top: 20px;
}
#rtbcb-real-treasury-overview-results .notice {
    margin: 5px 0;
}
#rtbcb-real-treasury-overview-results div[style*="background"] {
    white-space: pre-wrap;
    line-height: 1.6;
}
</style>

<script>
// Ensure ajaxurl is available
<?php if ( ! isset( $GLOBALS['ajaxurl'] ) || empty( $GLOBALS['ajaxurl'] ) ) : ?>
var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
<?php endif; ?>
</script>
