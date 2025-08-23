<?php
/**
 * Test Category Recommendation admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$company_overview    = get_option( 'rtbcb_company_overview', '' );
$industry_insights   = get_option( 'rtbcb_industry_insights', '' );
$treasury_challenges = get_option( 'rtbcb_treasury_challenges', '' );
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Test Category Recommendation Generation', 'rtbcb' ); ?></h1>

    <form id="rtbcb-category-recommendation-form">
        <table class="form-table">
            <tr>
                <th scope="row"><label><?php esc_html_e( 'Company Overview', 'rtbcb' ); ?></label></th>
                <td>
                    <textarea readonly class="large-text" rows="5"><?php echo esc_textarea( $company_overview ); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row"><label><?php esc_html_e( 'Industry Insights', 'rtbcb' ); ?></label></th>
                <td>
                    <textarea readonly class="large-text" rows="5"><?php echo esc_textarea( $industry_insights ); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row"><label><?php esc_html_e( 'Treasury Challenges', 'rtbcb' ); ?></label></th>
                <td>
                    <textarea readonly class="large-text" rows="5"><?php echo esc_textarea( $treasury_challenges ); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb-extra-requirements"><?php esc_html_e( 'Additional Requirements', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <textarea id="rtbcb-extra-requirements" class="large-text" rows="3" placeholder="<?php esc_attr_e( 'Optional - add any extra considerations...', 'rtbcb' ); ?>"></textarea>
                    <?php wp_nonce_field( 'rtbcb_test_category_recommendation', 'rtbcb_test_category_recommendation_nonce' ); ?>
                </td>
            </tr>
        </table>
        <p class="submit">
            <button type="button" id="rtbcb-generate-category-recommendation" class="button button-primary"><?php esc_html_e( 'Generate Recommendation', 'rtbcb' ); ?></button>
        </p>
    </form>

    <div id="rtbcb-category-recommendation-results"></div>
</div>
