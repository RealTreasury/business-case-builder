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

    <form id="rtbcb-test-recommended-category-form">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="rtbcb-company-size"><?php esc_html_e( 'Company Size', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <select id="rtbcb-company-size">
                        <option value=""><?php esc_html_e( 'Select size', 'rtbcb' ); ?></option>
                        <option value="lt50m"><?php esc_html_e( '<$50M', 'rtbcb' ); ?></option>
                        <option value="50m-500m"><?php esc_html_e( '$50M-$500M', 'rtbcb' ); ?></option>
                        <option value="500m-2b"><?php esc_html_e( '$500M-$2B', 'rtbcb' ); ?></option>
                        <option value="gt2b"><?php esc_html_e( '>$2B', 'rtbcb' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb-treasury-complexity"><?php esc_html_e( 'Treasury Complexity', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <select id="rtbcb-treasury-complexity">
                        <option value=""><?php esc_html_e( 'Select complexity', 'rtbcb' ); ?></option>
                        <option value="low"><?php esc_html_e( 'Low', 'rtbcb' ); ?></option>
                        <option value="medium"><?php esc_html_e( 'Medium', 'rtbcb' ); ?></option>
                        <option value="high"><?php esc_html_e( 'High', 'rtbcb' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Primary Pain Points', 'rtbcb' ); ?></th>
                <td>
                    <label><input type="checkbox" name="pain_points[]" value="manual_processes" /> <?php esc_html_e( 'Manual Processes', 'rtbcb' ); ?></label><br />
                    <label><input type="checkbox" name="pain_points[]" value="lack_visibility" /> <?php esc_html_e( 'Lack of Visibility', 'rtbcb' ); ?></label><br />
                    <label><input type="checkbox" name="pain_points[]" value="compliance" /> <?php esc_html_e( 'Compliance', 'rtbcb' ); ?></label><br />
                    <label><input type="checkbox" name="pain_points[]" value="integration" /> <?php esc_html_e( 'Integration Challenges', 'rtbcb' ); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb-budget-range"><?php esc_html_e( 'Budget Range', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <select id="rtbcb-budget-range">
                        <option value=""><?php esc_html_e( 'Select budget', 'rtbcb' ); ?></option>
                        <option value="lt50k"><?php esc_html_e( '<$50k', 'rtbcb' ); ?></option>
                        <option value="50k-100k"><?php esc_html_e( '$50k-$100k', 'rtbcb' ); ?></option>
                        <option value="100k-500k"><?php esc_html_e( '$100k-$500k', 'rtbcb' ); ?></option>
                        <option value="gt500k"><?php esc_html_e( '>$500k', 'rtbcb' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb-implementation-timeline"><?php esc_html_e( 'Implementation Timeline', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <select id="rtbcb-implementation-timeline">
                        <option value=""><?php esc_html_e( 'Select timeline', 'rtbcb' ); ?></option>
                        <option value="0-3"><?php esc_html_e( '0-3 months', 'rtbcb' ); ?></option>
                        <option value="3-6"><?php esc_html_e( '3-6 months', 'rtbcb' ); ?></option>
                        <option value="6-12"><?php esc_html_e( '6-12 months', 'rtbcb' ); ?></option>
                        <option value="12+"><?php esc_html_e( '12+ months', 'rtbcb' ); ?></option>
                    </select>
                </td>
            </tr>
        </table>

        <?php wp_nonce_field( 'rtbcb_test_category_recommendation', 'rtbcb_test_category_recommendation_nonce' ); ?>

        <p class="submit">
            <button type="button" id="rtbcb-generate-recommended-category" class="button button-primary">
                <?php esc_html_e( 'Generate Recommendation', 'rtbcb' ); ?>
            </button>
        </p>
    </form>

    <div id="rtbcb-recommended-category-results"></div>
</div>
