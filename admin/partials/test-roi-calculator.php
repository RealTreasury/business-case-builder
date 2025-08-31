<?php
/**
 * Partial for Test ROI Calculator section.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

defined( 'ABSPATH' ) || exit;

if ( ! rtbcb_require_completed_steps( 'rtbcb-test-roi-calculator', false ) ) {
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

$results = [];

if ( isset( $_POST['rtbcb_calculate_roi'] ) && check_admin_referer( 'rtbcb_calculate_roi', 'rtbcb_calculate_roi_nonce' ) ) {
    $results        = RTBCB_Calculator::calculate_roi( $company );
    $recommendation = RTBCB_Category_Recommender::recommend_category( $company );
    $results        = RTBCB_Calculator::calculate_category_refined_roi( $company, $recommendation['category_info'] );
    update_option( 'rtbcb_roi_results', $results );
}

$results = ! empty( $results ) ? $results : get_option( 'rtbcb_roi_results', [] );
?>
<h2><?php esc_html_e( 'Test ROI Calculator', 'rtbcb' ); ?></h2>
<p class="description"><?php esc_html_e( 'Calculate three-year ROI scenarios for the recommended solution.', 'rtbcb' ); ?></p>
<p class="rtbcb-data-source">
    <span class="rtbcb-data-status rtbcb-status-financial-analysis">⚪ <?php esc_html_e( 'Generate new', 'rtbcb' ); ?></span>
    <a href="#rtbcb-comprehensive-analysis" class="rtbcb-view-source" style="display:none;">
        <?php esc_html_e( 'View Source Data', 'rtbcb' ); ?>
    </a>
</p>
<form method="post">
    <?php wp_nonce_field( 'rtbcb_calculate_roi', 'rtbcb_calculate_roi_nonce' ); ?>
    <p class="submit">
        <button type="submit" name="rtbcb_calculate_roi" class="button button-primary"><?php esc_html_e( 'Calculate ROI', 'rtbcb' ); ?></button>
    </p>
</form>
<?php if ( ! empty( $results ) ) : ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Scenario', 'rtbcb' ); ?></th>
                <th><?php esc_html_e( '3-Year Benefit', 'rtbcb' ); ?></th>
                <th><?php esc_html_e( 'ROI %', 'rtbcb' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $results as $scenario => $data ) : ?>
            <tr>
                <td><?php echo esc_html( ucfirst( $scenario ) ); ?></td>
                <td><?php echo esc_html( number_format_i18n( $data['total_annual_benefit'] * 3, 2 ) ); ?></td>
                <td><?php echo esc_html( number_format_i18n( $data['roi_percentage'], 2 ) ); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
