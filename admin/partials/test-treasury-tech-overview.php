<?php
/**
 * Partial: Test Treasury Technology Overview section.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! rtbcb_require_completed_steps( 'rtbcb-test-treasury-tech-overview' ) ) {
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

$company_name       = isset( $company['name'] ) ? $company['name'] : '';
$company_size       = isset( $company['size'] ) ? $company['size'] : '';
$company_complexity = isset( $company['complexity'] ) ? $company['complexity'] : '';
$company_challenges = isset( $company['challenges'] ) ? (array) $company['challenges'] : [];

$areas = [
    'cash'      => __( 'Cash Management', 'rtbcb' ),
    'payments'  => __( 'Payments', 'rtbcb' ),
    'risk'      => __( 'Risk Management', 'rtbcb' ),
    'liquidity' => __( 'Liquidity', 'rtbcb' ),
    'analytics' => __( 'Analytics', 'rtbcb' ),
];

$suggested_focus_areas = array_intersect( array_keys( $areas ), array_map( 'sanitize_text_field', $company_challenges ) );
if ( empty( $suggested_focus_areas ) && ! empty( $company_size ) ) {
    $numeric_size = intval( preg_replace( '/[^0-9]/', '', (string) $company_size ) );
    if ( $numeric_size > 1000 ) {
        $suggested_focus_areas = [ 'liquidity', 'risk' ];
    } else {
        $suggested_focus_areas = [ 'cash' ];
    }
}
?>
<h2><?php esc_html_e( 'Test Treasury Technology Overview', 'rtbcb' ); ?></h2>
<?php if ( $company_name || $company_size || $company_complexity ) : ?>
    <p><?php printf(
        esc_html__( 'Company: %1$s | Size: %2$s | Complexity: %3$s', 'rtbcb' ),
        esc_html( $company_name ),
        esc_html( $company_size ),
        esc_html( $company_complexity )
    ); ?></p>
<?php endif; ?>

<div class="card">
    <h3 class="title"><?php esc_html_e( 'Generate Overview', 'rtbcb' ); ?></h3>
    <p><?php esc_html_e( 'Select focus areas and company complexity to generate an overview.', 'rtbcb' ); ?></p>

    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e( 'Focus Areas', 'rtbcb' ); ?></th>
            <td>
                <?php foreach ( $areas as $value => $label ) : ?>
                    <label>
                        <input type="checkbox" name="rtbcb_focus_areas[]" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $suggested_focus_areas, true ) ); ?> />
                        <?php echo esc_html( $label ); ?>
                    </label><br />
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rtbcb-company-complexity"><?php esc_html_e( 'Company Complexity', 'rtbcb' ); ?></label>
            </th>
            <td>
                <select id="rtbcb-company-complexity">
                    <option value="simple" <?php selected( 'simple', $company_complexity ); ?>><?php esc_html_e( 'Simple', 'rtbcb' ); ?></option>
                    <option value="moderate" <?php selected( 'moderate', $company_complexity ); ?>><?php esc_html_e( 'Moderate', 'rtbcb' ); ?></option>
                    <option value="complex" <?php selected( 'complex', $company_complexity ); ?>><?php esc_html_e( 'Complex', 'rtbcb' ); ?></option>
                </select>
                <?php wp_nonce_field( 'rtbcb_test_treasury_tech_overview', 'rtbcb_test_treasury_tech_overview_nonce' ); ?>
            </td>
        </tr>
    </table>

    <p class="submit">
        <button type="button" id="rtbcb-generate-treasury-tech-overview" class="button button-primary">
            <?php esc_html_e( 'Generate Overview', 'rtbcb' ); ?>
        </button>
        <button type="button" id="rtbcb-clear-treasury-tech-overview" class="button">
            <?php esc_html_e( 'Clear', 'rtbcb' ); ?>
        </button>
    </p>
</div>

<div id="rtbcb-treasury-tech-overview-results"></div>
<?php rtbcb_render_test_navigation( 'rtbcb-test-treasury-tech-overview' ); ?>

<style>
#rtbcb-treasury-tech-overview-results {
    margin-top: 20px;
}
#rtbcb-treasury-tech-overview-results .notice {
    margin: 5px 0;
}
#rtbcb-treasury-tech-overview-results div[style*="background"] {
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
