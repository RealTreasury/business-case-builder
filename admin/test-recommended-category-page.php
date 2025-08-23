<?php
/**
 * Test Category Recommendation admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Test Category Recommendation Generation', 'rtbcb' ); ?></h1>

    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="rtbcb-company-size"><?php esc_html_e( 'Company Size', 'rtbcb' ); ?></label>
            </th>
            <td>
                <select id="rtbcb-company-size" class="regular-text">
                    <option value=""><?php esc_html_e( 'Select company size', 'rtbcb' ); ?></option>
                    <option value="&lt;$50M"><?php esc_html_e( '<$50M', 'rtbcb' ); ?></option>
                    <option value="$50M-$500M"><?php esc_html_e( '$50M-$500M', 'rtbcb' ); ?></option>
                    <option value="$500M-$2B"><?php esc_html_e( '$500M-$2B', 'rtbcb' ); ?></option>
                    <option value="&gt;$2B"><?php esc_html_e( '>$2B', 'rtbcb' ); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rtbcb-treasury-complexity"><?php esc_html_e( 'Treasury Complexity', 'rtbcb' ); ?></label>
            </th>
            <td>
                <select id="rtbcb-treasury-complexity" class="regular-text">
                    <option value=""><?php esc_html_e( 'Select complexity', 'rtbcb' ); ?></option>
                    <option value="basic"><?php esc_html_e( 'Basic', 'rtbcb' ); ?></option>
                    <option value="moderate"><?php esc_html_e( 'Moderate', 'rtbcb' ); ?></option>
                    <option value="advanced"><?php esc_html_e( 'Advanced', 'rtbcb' ); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Primary Pain Points', 'rtbcb' ); ?></th>
            <td>
                <fieldset>
                    <label><input type="checkbox" name="pain_points[]" value="manual_processes" /> <?php esc_html_e( 'Manual Processes', 'rtbcb' ); ?></label><br />
                    <label><input type="checkbox" name="pain_points[]" value="poor_visibility" /> <?php esc_html_e( 'Poor Cash Visibility', 'rtbcb' ); ?></label><br />
                    <label><input type="checkbox" name="pain_points[]" value="forecast_accuracy" /> <?php esc_html_e( 'Forecast Accuracy', 'rtbcb' ); ?></label><br />
                    <label><input type="checkbox" name="pain_points[]" value="compliance_risk" /> <?php esc_html_e( 'Compliance & Risk', 'rtbcb' ); ?></label><br />
                    <label><input type="checkbox" name="pain_points[]" value="bank_fees" /> <?php esc_html_e( 'High Bank Fees', 'rtbcb' ); ?></label><br />
                    <label><input type="checkbox" name="pain_points[]" value="integration_issues" /> <?php esc_html_e( 'System Integration', 'rtbcb' ); ?></label>
                </fieldset>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rtbcb-budget-range"><?php esc_html_e( 'Budget Range', 'rtbcb' ); ?></label>
            </th>
            <td>
                <select id="rtbcb-budget-range" class="regular-text">
                    <option value=""><?php esc_html_e( 'Select a range', 'rtbcb' ); ?></option>
                    <option value="50-100"><?php esc_html_e( '$50K-$100K', 'rtbcb' ); ?></option>
                    <option value="100-250"><?php esc_html_e( '$100K-$250K', 'rtbcb' ); ?></option>
                    <option value="250-500"><?php esc_html_e( '$250K-$500K', 'rtbcb' ); ?></option>
                    <option value="500+"><?php esc_html_e( '$500K+', 'rtbcb' ); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rtbcb-implementation-timeline"><?php esc_html_e( 'Implementation Timeline', 'rtbcb' ); ?></label>
            </th>
            <td>
                <select id="rtbcb-implementation-timeline" class="regular-text">
                    <option value=""><?php esc_html_e( 'Select a timeline', 'rtbcb' ); ?></option>
                    <option value="3-6"><?php esc_html_e( '3-6 months', 'rtbcb' ); ?></option>
                    <option value="6-12"><?php esc_html_e( '6-12 months', 'rtbcb' ); ?></option>
                    <option value="12+"><?php esc_html_e( '12+ months', 'rtbcb' ); ?></option>
                </select>
            </td>
        </tr>
    </table>

    <p class="submit">
        <button type="button" id="rtbcb-generate-category" class="button button-primary">
            <?php esc_html_e( 'Generate Recommendation', 'rtbcb' ); ?>
        </button>
    </p>

    <?php wp_nonce_field( 'rtbcb_test_category_recommendation', 'rtbcb_test_category_recommendation_nonce' ); ?>

    <div id="rtbcb-category-results"></div>
</div>

<script>
<?php if ( ! isset( $GLOBALS['ajaxurl'] ) || empty( $GLOBALS['ajaxurl'] ) ) : ?>
var ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
<?php endif; ?>
</script>
