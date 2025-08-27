<?php
/**
 * Partial for Test Category Recommendation section.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! rtbcb_require_completed_steps( 'rtbcb-test-recommended-category' ) ) {
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

$company_overview    = get_option( 'rtbcb_company_overview', '' );
$industry_insights   = get_option( 'rtbcb_industry_insights', '' );
$treasury_challenges = get_option( 'rtbcb_treasury_challenges', '' );
?>
<h2><?php esc_html_e( 'Test Category Recommendation Generation', 'rtbcb' ); ?></h2>
<p class="description"><?php esc_html_e( 'Receive an AI-suggested treasury technology category based on gathered data.', 'rtbcb' ); ?></p>
<?php $rtbcb_last = rtbcb_get_last_test_result( 'rtbcb-test-recommended-category', $test_results ?? [] ); ?>
<?php if ( $rtbcb_last ) : ?>
    <div class="notice notice-info" role="status">
        <p><strong><?php esc_html_e( 'Status:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['status'] ); ?></p>
        <p><strong><?php esc_html_e( 'Message:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['message'] ); ?></p>
        <p><strong><?php esc_html_e( 'Timestamp:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['timestamp'] ); ?></p>
        <p class="submit">
            <button type="button" class="button" id="rtbcb-rerun-category" data-section="rtbcb-test-recommended-category">
                <?php esc_html_e( 'Regenerate', 'rtbcb' ); ?>
            </button>
        </p>
    </div>
<?php endif; ?>
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
                <textarea readonly class="large-text" rows="5"><?php echo esc_textarea( $treasury_challenges ); ?></textarea></td>
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
<div id="rtbcb-category-recommendation-card" class="rtbcb-result-card" style="display:none;">
    <details>
        <summary><?php esc_html_e( 'Recommendation', 'rtbcb' ); ?></summary>
        <div id="rtbcb-category-recommendation-results"></div>
        <p class="rtbcb-actions">
            <button type="button" id="rtbcb-regenerate-category-recommendation" class="button"><?php esc_html_e( 'Regenerate', 'rtbcb' ); ?></button>
            <button type="button" id="rtbcb-copy-category-recommendation" class="button"><?php esc_html_e( 'Copy', 'rtbcb' ); ?></button>
        </p>
    </details>
</div>
<script>
document.getElementById( 'rtbcb-rerun-category' )?.addEventListener( 'click', function() {
    document.getElementById( 'rtbcb-generate-category-recommendation' ).click();
});
</script>
