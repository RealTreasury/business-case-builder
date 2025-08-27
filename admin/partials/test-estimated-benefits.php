<?php
/**
 * Partial for Test Estimated Benefits section.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! rtbcb_require_completed_steps( 'rtbcb-test-estimated-benefits' ) ) {
    return;
}

$company = rtbcb_get_current_company();
if ( empty( $company ) ) {
    $overview_url = admin_url( 'admin.php?page=rtbcb-test-dashboard#rtbcb-test-company-overview' );
    echo '<div class="notice notice-error"><p>' . sprintf(
        esc_html__( 'No company data found. Please run the %s first.', 'rtbcb' ),
        '<a href="' . esc_url( $overview_url ) . '">' . esc_html__( 'Company Overview', 'rtbcb' ) . '</a>'
    ) . '</p></div>';
    return;
}

$company_data         = get_option( 'rtbcb_company_data', [] );
$recommended_category = get_option( 'rtbcb_last_recommended_category', '' );
$categories           = RTBCB_Category_Recommender::get_all_categories();
?>
<h2><?php esc_html_e( 'Test Estimated Benefits', 'rtbcb' ); ?></h2>
<p class="description"><?php esc_html_e( 'Estimate potential savings and efficiency gains based on company metrics.', 'rtbcb' ); ?></p>
<?php $rtbcb_last = rtbcb_get_last_test_result( 'rtbcb-test-estimated-benefits', $test_results ?? [] ); ?>
<?php if ( $rtbcb_last ) : ?>
    <div class="notice notice-info" role="status">
        <p><strong><?php esc_html_e( 'Status:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['status'] ); ?></p>
        <p><strong><?php esc_html_e( 'Message:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['message'] ); ?></p>
        <p><strong><?php esc_html_e( 'Timestamp:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['timestamp'] ); ?></p>
        <p class="submit">
            <button type="button" class="button" id="rtbcb-rerun-benefits" data-section="rtbcb-test-estimated-benefits">
                <?php esc_html_e( 'Regenerate', 'rtbcb' ); ?>
            </button>
        </p>
    </div>
<?php endif; ?>
<form id="rtbcb-benefits-estimate-form">
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="rtbcb-test-revenue"><?php esc_html_e( 'Company Annual Revenue', 'rtbcb' ); ?></label>
            </th>
            <td>
                <input type="number" id="rtbcb-test-revenue" value="<?php echo esc_attr( $company_data['revenue'] ?? '' ); ?>" />
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rtbcb-test-staff-count"><?php esc_html_e( 'Treasury Staff Count', 'rtbcb' ); ?></label>
            </th>
            <td>
                <input type="number" id="rtbcb-test-staff-count" value="<?php echo esc_attr( $company_data['staff_count'] ?? '' ); ?>" />
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rtbcb-test-efficiency"><?php esc_html_e( 'Current Process Efficiency', 'rtbcb' ); ?></label>
            </th>
            <td>
                <input type="range" id="rtbcb-test-efficiency" min="1" max="10" value="<?php echo esc_attr( $company_data['efficiency'] ?? '' ); ?>" />
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rtbcb-test-category"><?php esc_html_e( 'Category', 'rtbcb' ); ?></label>
            </th>
            <td>
                <select id="rtbcb-test-category">
                    <?php foreach ( $categories as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $recommended_category ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php wp_nonce_field( 'rtbcb_test_estimated_benefits', 'rtbcb_test_estimated_benefits_nonce' ); ?>
            </td>
        </tr>
    </table>
    <p class="submit">
        <button type="submit" id="rtbcb-generate-benefits-estimate" class="button button-primary">
            <?php esc_html_e( 'Generate Estimate', 'rtbcb' ); ?>
        </button>
    </p>
</form>
<div id="rtbcb-benefits-estimate-card" class="rtbcb-result-card">
    <details>
        <summary><?php esc_html_e( 'Estimated Benefits', 'rtbcb' ); ?></summary>
        <div id="rtbcb-benefits-estimate-results"></div>
    </details>
</div>
<script>
document.getElementById( 'rtbcb-rerun-benefits' )?.addEventListener( 'click', function() {
    jQuery( '#rtbcb-benefits-estimate-form' ).trigger( 'submit' );
});
</script>
