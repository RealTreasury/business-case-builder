<?php
/**
 * Test Recommended Category admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Test Category Recommendation Generation', 'rtbcb' ); ?></h1>

    <div class="card">
        <h2 class="title"><?php esc_html_e( 'Input Parameters', 'rtbcb' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="rtbcb-company-size"><?php esc_html_e( 'Company Size', 'rtbcb' ); ?></label></th>
                <td>
                    <select id="rtbcb-company-size">
                        <option value=""><?php esc_html_e( 'Select size', 'rtbcb' ); ?></option>
                        <option value="<$50M">&lt;$50M</option>
                        <option value="$50M-$500M">$50M-$500M</option>
                        <option value="$500M-$2B">$500M-$2B</option>
                        <option value=">$2B">&gt;$2B</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="rtbcb-treasury-complexity"><?php esc_html_e( 'Treasury Complexity', 'rtbcb' ); ?></label></th>
                <td>
                    <select id="rtbcb-treasury-complexity">
                        <option value=""><?php esc_html_e( 'Select complexity', 'rtbcb' ); ?></option>
                        <option value="simple"><?php esc_html_e( 'Simple', 'rtbcb' ); ?></option>
                        <option value="moderate"><?php esc_html_e( 'Moderate', 'rtbcb' ); ?></option>
                        <option value="complex"><?php esc_html_e( 'Complex', 'rtbcb' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Primary Pain Points', 'rtbcb' ); ?></th>
                <td>
<?php
$pain_points = [
    'manual_processes'   => __( 'Manual Processes', 'rtbcb' ),
    'poor_visibility'    => __( 'Poor Visibility', 'rtbcb' ),
    'forecast_accuracy'  => __( 'Forecast Accuracy', 'rtbcb' ),
    'compliance_risk'    => __( 'Compliance Risk', 'rtbcb' ),
    'bank_fees'          => __( 'Bank Fees', 'rtbcb' ),
    'integration_issues' => __( 'Integration Issues', 'rtbcb' ),
];
foreach ( $pain_points as $value => $label ) :
?>
                    <label>
                        <input type="checkbox" class="rtbcb-pain-point" value="<?php echo esc_attr( $value ); ?>" />
                        <?php echo esc_html( $label ); ?>
                    </label><br />
<?php endforeach; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="rtbcb-budget-range"><?php esc_html_e( 'Budget Range', 'rtbcb' ); ?></label></th>
                <td>
                    <select id="rtbcb-budget-range">
                        <option value=""><?php esc_html_e( 'Select budget', 'rtbcb' ); ?></option>
                        <option value="<50K">&lt;$50K</option>
                        <option value="50K-150K">$50K-$150K</option>
                        <option value="150K-500K">$150K-$500K</option>
                        <option value=">500K">&gt;$500K</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="rtbcb-implementation-timeline"><?php esc_html_e( 'Implementation Timeline', 'rtbcb' ); ?></label></th>
                <td>
                    <select id="rtbcb-implementation-timeline">
                        <option value=""><?php esc_html_e( 'Select timeline', 'rtbcb' ); ?></option>
                        <option value="immediate"><?php esc_html_e( 'Immediate', 'rtbcb' ); ?></option>
                        <option value="3-6_months"><?php esc_html_e( '3-6 Months', 'rtbcb' ); ?></option>
                        <option value="6-12_months"><?php esc_html_e( '6-12 Months', 'rtbcb' ); ?></option>
                        <option value="12_plus"><?php esc_html_e( '12+ Months', 'rtbcb' ); ?></option>
                    </select>
                    <?php wp_nonce_field( 'rtbcb_test_category_recommendation', 'rtbcb_test_category_recommendation_nonce' ); ?>
                </td>
            </tr>
        </table>
        <p class="submit">
            <button type="button" id="rtbcb-generate-recommended-category" class="button button-primary">
                <?php esc_html_e( 'Generate Recommendation', 'rtbcb' ); ?>
            </button>
        </p>
    </div>

    <div id="rtbcb-recommended-category-results"></div>
</div>

<style>
#rtbcb-recommended-category-results {
    margin-top:20px;
}
</style>
<script>
<?php if ( ! isset( $GLOBALS['ajaxurl'] ) || empty( $GLOBALS['ajaxurl'] ) ) : ?>
var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
<?php endif; ?>
</script>
