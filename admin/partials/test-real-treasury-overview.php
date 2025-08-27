<?php
/**
 * Partial: Test Real Treasury Overview section.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! rtbcb_require_completed_steps( 'rtbcb-test-real-treasury-overview' ) ) {
    return;
}

$company = rtbcb_get_current_company();
if ( empty( $company ) ) {
    $overview_url = admin_url( 'admin.php?page=rtbcb-test-dashboard&tab=company-overview' );
    echo '<div class="notice notice-error"><p>' . sprintf(
        esc_html__( 'No company data found. Please run the %s first.', 'rtbcb' ),
        '<a href="' . esc_url( $overview_url ) . '">' . esc_html__( 'Company Overview', 'rtbcb' ) . '</a>'
    ) . '</p></div>';
    return;
}
?>
<h2><?php esc_html_e( 'Test Real Treasury Overview Generation', 'rtbcb' ); ?></h2>

<div class="card">
    <h3 class="title"><?php esc_html_e( 'Generate Real Treasury Overview', 'rtbcb' ); ?></h3>
    <p><?php esc_html_e( 'Configure options and generate an overview.', 'rtbcb' ); ?></p>

    <h4><?php esc_html_e( 'Company Summary', 'rtbcb' ); ?></h4>
    <p id="rtbcb-company-summary"></p>

    <h4><?php esc_html_e( 'Identified Challenges', 'rtbcb' ); ?></h4>
    <ul id="rtbcb-company-challenges"></ul>

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
                <label for="rtbcb-override-categories"><?php esc_html_e( 'Override Categories', 'rtbcb' ); ?></label>
            </th>
            <td>
                <label>
                    <input type="checkbox" id="rtbcb-override-categories" />
                    <?php esc_html_e( 'Specify vendor categories', 'rtbcb' ); ?>
                </label>
                <select id="rtbcb-vendor-categories" multiple size="5" style="display:none;">
                    <option value="tms"><?php esc_html_e( 'TMS', 'rtbcb' ); ?></option>
                    <option value="cash_management"><?php esc_html_e( 'Cash Management', 'rtbcb' ); ?></option>
                    <option value="payments"><?php esc_html_e( 'Payments', 'rtbcb' ); ?></option>
                    <option value="liquidity"><?php esc_html_e( 'Liquidity', 'rtbcb' ); ?></option>
                    <option value="risk_management"><?php esc_html_e( 'Risk Management', 'rtbcb' ); ?></option>
                </select>
            </td>
        </tr>
    </table>

    <?php wp_nonce_field( 'rtbcb_test_real_treasury_overview', 'rtbcb_test_real_treasury_overview_nonce' ); ?>

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
<?php rtbcb_render_test_navigation( 'rtbcb-test-real-treasury-overview' ); ?>

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
