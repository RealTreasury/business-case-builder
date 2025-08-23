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
    <h1><?php esc_html_e( 'Test Recommended Category', 'rtbcb' ); ?></h1>
    <form id="rtbcb-recommended-category-form">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="rtbcb-company-size"><?php esc_html_e( 'Company Size', 'rtbcb' ); ?></label></th>
                <td>
                    <select id="rtbcb-company-size" name="company_size">
                        <option value=""><?php esc_html_e( 'Select...', 'rtbcb' ); ?></option>
                        <option value="&lt;$50M"><?php esc_html_e( 'Small Business (&lt;$50M)', 'rtbcb' ); ?></option>
                        <option value="$50M-$500M"><?php esc_html_e( 'Mid-Market ($50M-$500M)', 'rtbcb' ); ?></option>
                        <option value="$500M-$2B"><?php esc_html_e( 'Large Enterprise ($500M-$2B)', 'rtbcb' ); ?></option>
                        <option value="&gt;$2B"><?php esc_html_e( 'Fortune 500 (&gt;$2B)', 'rtbcb' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="rtbcb-complexity"><?php esc_html_e( 'Operational Complexity', 'rtbcb' ); ?></label></th>
                <td>
                    <select id="rtbcb-complexity" name="complexity">
                        <option value=""><?php esc_html_e( 'Select...', 'rtbcb' ); ?></option>
                        <option value="low"><?php esc_html_e( 'Low', 'rtbcb' ); ?></option>
                        <option value="medium"><?php esc_html_e( 'Medium', 'rtbcb' ); ?></option>
                        <option value="high"><?php esc_html_e( 'High', 'rtbcb' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="rtbcb-budget-range"><?php esc_html_e( 'Budget Range', 'rtbcb' ); ?></label></th>
                <td>
                    <select id="rtbcb-budget-range" name="budget_range">
                        <option value=""><?php esc_html_e( 'Select...', 'rtbcb' ); ?></option>
                        <option value="50-100"><?php esc_html_e( '$50K-$100K', 'rtbcb' ); ?></option>
                        <option value="100-250"><?php esc_html_e( '$100K-$250K', 'rtbcb' ); ?></option>
                        <option value="250-500"><?php esc_html_e( '$250K-$500K', 'rtbcb' ); ?></option>
                        <option value="500+"><?php esc_html_e( '$500K+', 'rtbcb' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="rtbcb-timeline"><?php esc_html_e( 'Implementation Timeline', 'rtbcb' ); ?></label></th>
                <td>
                    <select id="rtbcb-timeline" name="timeline">
                        <option value=""><?php esc_html_e( 'Select...', 'rtbcb' ); ?></option>
                        <option value="3-6"><?php esc_html_e( '3-6 months', 'rtbcb' ); ?></option>
                        <option value="6-12"><?php esc_html_e( '6-12 months', 'rtbcb' ); ?></option>
                        <option value="12+"><?php esc_html_e( '12+ months', 'rtbcb' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Pain Points', 'rtbcb' ); ?></th>
                <td>
                    <fieldset>
                        <label><input type="checkbox" name="pain_points[]" value="manual_processes" /> <?php esc_html_e( 'Manual Processes', 'rtbcb' ); ?></label><br />
                        <label><input type="checkbox" name="pain_points[]" value="poor_visibility" /> <?php esc_html_e( 'Poor Visibility', 'rtbcb' ); ?></label><br />
                        <label><input type="checkbox" name="pain_points[]" value="forecast_accuracy" /> <?php esc_html_e( 'Forecast Accuracy', 'rtbcb' ); ?></label><br />
                        <label><input type="checkbox" name="pain_points[]" value="compliance_risk" /> <?php esc_html_e( 'Compliance Risk', 'rtbcb' ); ?></label><br />
                        <label><input type="checkbox" name="pain_points[]" value="bank_fees" /> <?php esc_html_e( 'Bank Fees', 'rtbcb' ); ?></label><br />
                        <label><input type="checkbox" name="pain_points[]" value="integration_issues" /> <?php esc_html_e( 'Integration Issues', 'rtbcb' ); ?></label>
                    </fieldset>
                </td>
            </tr>
        </table>
        <?php wp_nonce_field( 'rtbcb_test_recommended_category', 'nonce' ); ?>
        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Get Recommendation', 'rtbcb' ); ?></button>
            <button type="button" id="rtbcb-clear-recommended-category" class="button"><?php esc_html_e( 'Clear', 'rtbcb' ); ?></button>
        </p>
    </form>
    <div id="rtbcb-recommended-category-results"></div>
</div>
<style>
#rtbcb-recommended-category-results {
    margin-top: 20px;
}
#rtbcb-recommended-category-results .notice {
    margin: 5px 0;
}
</style>
<script>
// Ensure ajaxurl is available
<?php if ( ! isset( $GLOBALS['ajaxurl'] ) || empty( $GLOBALS['ajaxurl'] ) ) : ?>
var ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
<?php endif; ?>
</script>
