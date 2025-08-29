<?php
/**
 * Partial for Test Real Treasury Overview section.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! rtbcb_require_completed_steps( 'rtbcb-test-real-treasury-overview', false ) ) {
    echo '<div class="notice notice-warning inline"><p>' .
        esc_html__( 'Please complete previous steps before accessing this section.', 'rtbcb' ) .
        '</p></div>';
    return;
}

$company = rtbcb_get_current_company();
if ( empty( $company ) ) {
$overview_url = admin_url( 'admin.php?page=rtbcb-test-dashboard#rtbcb-phase1' );
    echo '<div class="notice notice-error"><p>' . sprintf(
        esc_html__( 'No company data found. Please run the %s first.', 'rtbcb' ),
        '<a href="' . esc_url( $overview_url ) . '">' . esc_html__( 'Company Overview', 'rtbcb' ) . '</a>'
    ) . '</p></div>';
    return;
}
?>
<h2><?php esc_html_e( 'Test Real Treasury Overview Generation', 'rtbcb' ); ?></h2>
<p class="description"><?php esc_html_e( 'Create a tailored treasury overview based on company data and selected options.', 'rtbcb' ); ?></p>
<?php $rtbcb_last = rtbcb_get_last_test_result( 'rtbcb-test-real-treasury-overview', $test_results ?? [] ); ?>
<?php if ( $rtbcb_last ) : ?>
    <div class="notice notice-info" role="status">
        <p><strong><?php esc_html_e( 'Status:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['status'] ); ?></p>
        <p><strong><?php esc_html_e( 'Message:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['message'] ); ?></p>
        <p><strong><?php esc_html_e( 'Timestamp:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['timestamp'] ); ?></p>
        <p class="submit">
            <button type="button" class="button" id="rtbcb-rerun-real-treasury" data-section="rtbcb-test-real-treasury-overview">
                <?php esc_html_e( 'Regenerate', 'rtbcb' ); ?>
            </button>
        </p>
    </div>
<?php endif; ?>
<div class="card">
    <h3 class="title"><?php esc_html_e( 'Generate Real Treasury Overview', 'rtbcb' ); ?></h3>
    <p><?php esc_html_e( 'Configure options and generate an overview.', 'rtbcb' ); ?></p>

    <h3><?php esc_html_e( 'Company Summary', 'rtbcb' ); ?></h3>
    <p id="rtbcb-company-summary"></p>

    <h3><?php esc_html_e( 'Identified Challenges', 'rtbcb' ); ?></h3>
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
<div id="rtbcb-real-treasury-overview-card" class="rtbcb-result-card" style="display:none;">
    <details>
        <summary><?php esc_html_e( 'Generated Overview', 'rtbcb' ); ?></summary>
        <div id="rtbcb-real-treasury-overview-results"></div>
        <div id="rtbcb-real-treasury-overview-meta" class="rtbcb-meta"></div>
        <p class="rtbcb-actions">
            <button type="button" id="rtbcb-regenerate-real-treasury-overview" class="button"><?php esc_html_e( 'Regenerate', 'rtbcb' ); ?></button>
            <button type="button" id="rtbcb-copy-real-treasury-overview" class="button"><?php esc_html_e( 'Copy', 'rtbcb' ); ?></button>
        </p>
    </details>
</div>
<style>
#rtbcb-real-treasury-overview-card details {
    margin-top: 20px;
}
#rtbcb-real-treasury-overview-results div[style*="background"] {
    white-space: pre-wrap;
    line-height: 1.6;
}
</style>
<script>
    document.getElementById( 'rtbcb-rerun-real-treasury' )?.addEventListener( 'click', function() {
        document.getElementById( 'rtbcb-generate-real-treasury-overview' ).click();
    });
</script>
