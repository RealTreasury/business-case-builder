<?php
/**
 * Admin page for testing category recommendation generation.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Test Category Recommendation Generation', 'rtbcb' ); ?></h1>
    <form id="rtbcb-test-category-form">
        <?php wp_nonce_field( 'rtbcb_test_category_recommendation', 'rtbcb_test_category_recommendation_nonce' ); ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="rtbcb-company-size"><?php esc_html_e( 'Company Size', 'rtbcb' ); ?></label></th>
                <td>
                    <select id="rtbcb-company-size" name="company_size">
                        <option value=""><?php esc_html_e( 'Select', 'rtbcb' ); ?></option>
                        <option value="<$50M"><?php esc_html_e( '<$50M', 'rtbcb' ); ?></option>
                        <option value="$50M-$500M"><?php esc_html_e( '$50M-$500M', 'rtbcb' ); ?></option>
                        <option value="$500M-$2B"><?php esc_html_e( '$500M-$2B', 'rtbcb' ); ?></option>
                        <option value=">$2B"><?php esc_html_e( '>$2B', 'rtbcb' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="rtbcb-treasury-complexity"><?php esc_html_e( 'Treasury Complexity', 'rtbcb' ); ?></label></th>
                <td>
                    <select id="rtbcb-treasury-complexity" name="treasury_complexity">
                        <option value=""><?php esc_html_e( 'Select', 'rtbcb' ); ?></option>
                        <option value="simple"><?php esc_html_e( 'Simple', 'rtbcb' ); ?></option>
                        <option value="moderate"><?php esc_html_e( 'Moderate', 'rtbcb' ); ?></option>
                        <option value="complex"><?php esc_html_e( 'Complex', 'rtbcb' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Primary Pain Points', 'rtbcb' ); ?></th>
                <td>
                    <label><input type="checkbox" name="pain_points[]" value="poor_visibility"> <?php esc_html_e( 'Poor Visibility', 'rtbcb' ); ?></label><br />
                    <label><input type="checkbox" name="pain_points[]" value="manual_processes"> <?php esc_html_e( 'Manual Processes', 'rtbcb' ); ?></label><br />
                    <label><input type="checkbox" name="pain_points[]" value="forecast_accuracy"> <?php esc_html_e( 'Forecast Accuracy', 'rtbcb' ); ?></label><br />
                    <label><input type="checkbox" name="pain_points[]" value="integration_issues"> <?php esc_html_e( 'Integration Issues', 'rtbcb' ); ?></label><br />
                    <label><input type="checkbox" name="pain_points[]" value="compliance_risk"> <?php esc_html_e( 'Compliance Risk', 'rtbcb' ); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="rtbcb-budget-range"><?php esc_html_e( 'Budget Range', 'rtbcb' ); ?></label></th>
                <td>
                    <select id="rtbcb-budget-range" name="budget_range">
                        <option value=""><?php esc_html_e( 'Select', 'rtbcb' ); ?></option>
                        <option value="<50k"><?php esc_html_e( '< $50k', 'rtbcb' ); ?></option>
                        <option value="50k-150k"><?php esc_html_e( '$50k-$150k', 'rtbcb' ); ?></option>
                        <option value="150k-500k"><?php esc_html_e( '$150k-$500k', 'rtbcb' ); ?></option>
                        <option value=">500k"><?php esc_html_e( '>$500k', 'rtbcb' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="rtbcb-implementation-timeline"><?php esc_html_e( 'Implementation Timeline', 'rtbcb' ); ?></label></th>
                <td>
                    <select id="rtbcb-implementation-timeline" name="implementation_timeline">
                        <option value=""><?php esc_html_e( 'Select', 'rtbcb' ); ?></option>
                        <option value="0-3m"><?php esc_html_e( '0-3 months', 'rtbcb' ); ?></option>
                        <option value="3-6m"><?php esc_html_e( '3-6 months', 'rtbcb' ); ?></option>
                        <option value="6-12m"><?php esc_html_e( '6-12 months', 'rtbcb' ); ?></option>
                        <option value=">12m"><?php esc_html_e( '12+ months', 'rtbcb' ); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <p>
            <button type="button" class="button button-primary" id="rtbcb-generate-category">
                <?php esc_html_e( 'Generate Recommendation', 'rtbcb' ); ?>
            </button>
        </p>
    </form>
    <div id="rtbcb-category-results"></div>
</div>
