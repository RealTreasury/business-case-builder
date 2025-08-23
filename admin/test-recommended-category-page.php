<?php
/**
 * Test Category Recommendation admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_company = rtbcb_get_current_company();
$clear_nonce     = wp_create_nonce( 'rtbcb_clear_current_company' );
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Test Category Recommendation Generation', 'rtbcb' ); ?></h1>
    <p>
        <button type="button" id="rtbcb-start-new-analysis" class="button">
            <?php esc_html_e( 'Start New Company Analysis', 'rtbcb' ); ?>
        </button>
    </p>

<?php if ( empty( $current_company ) ) : ?>
    <div class="notice notice-error">
        <p><?php esc_html_e( 'No company selected. Please run the company overview.', 'rtbcb' ); ?></p>
    </div>
<?php else : ?>

    <form id="rtbcb-category-recommendation-form">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="rtbcb-company-size"><?php esc_html_e( 'Company Size', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <select id="rtbcb-company-size">
                        <option value=""><?php esc_html_e( 'Select size', 'rtbcb' ); ?></option>
                        <option value="small"><?php esc_html_e( 'Small', 'rtbcb' ); ?></option>
                        <option value="medium"><?php esc_html_e( 'Medium', 'rtbcb' ); ?></option>
                        <option value="large"><?php esc_html_e( 'Large', 'rtbcb' ); ?></option>
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
                        <option value="basic"><?php esc_html_e( 'Basic', 'rtbcb' ); ?></option>
                        <option value="moderate"><?php esc_html_e( 'Moderate', 'rtbcb' ); ?></option>
                        <option value="advanced"><?php esc_html_e( 'Advanced', 'rtbcb' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e( 'Primary Pain Points', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <label><input type="checkbox" name="pain_points[]" value="visibility" /> <?php esc_html_e( 'Lack of visibility', 'rtbcb' ); ?></label><br />
                    <label><input type="checkbox" name="pain_points[]" value="automation" /> <?php esc_html_e( 'Need for automation', 'rtbcb' ); ?></label><br />
                    <label><input type="checkbox" name="pain_points[]" value="compliance" /> <?php esc_html_e( 'Compliance challenges', 'rtbcb' ); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb-budget-range"><?php esc_html_e( 'Budget Range', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <select id="rtbcb-budget-range">
                        <option value=""><?php esc_html_e( 'Select budget', 'rtbcb' ); ?></option>
                        <option value="under_50k"><?php esc_html_e( 'Under $50k', 'rtbcb' ); ?></option>
                        <option value="50k_150k"><?php esc_html_e( '$50k - $150k', 'rtbcb' ); ?></option>
                        <option value="over_150k"><?php esc_html_e( 'Over $150k', 'rtbcb' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb-timeline"><?php esc_html_e( 'Implementation Timeline', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <select id="rtbcb-timeline">
                        <option value=""><?php esc_html_e( 'Select timeline', 'rtbcb' ); ?></option>
                        <option value="0-3"><?php esc_html_e( '0-3 months', 'rtbcb' ); ?></option>
                        <option value="3-6"><?php esc_html_e( '3-6 months', 'rtbcb' ); ?></option>
                        <option value="6-12"><?php esc_html_e( '6-12 months', 'rtbcb' ); ?></option>
                    </select>
                    <?php wp_nonce_field( 'rtbcb_test_category_recommendation', 'rtbcb_test_category_recommendation_nonce' ); ?>
                </td>
            </tr>
        </table>
        <p class="submit">
            <button type="button" id="rtbcb-generate-category-recommendation" class="button button-primary"><?php esc_html_e( 'Generate Recommendation', 'rtbcb' ); ?></button>
        </p>
    </form>

    <div id="rtbcb-category-recommendation-results"></div>
<?php endif; ?>
</div>

<script>
// Ensure ajaxurl is available
<?php if ( ! isset( $GLOBALS['ajaxurl'] ) || empty( $GLOBALS['ajaxurl'] ) ) : ?>
var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
<?php endif; ?>
document.getElementById('rtbcb-start-new-analysis').addEventListener('click', function () {
    var data = new FormData();
    data.append('action', 'rtbcb_clear_current_company');
    data.append('nonce', '<?php echo esc_js( $clear_nonce ); ?>');
    fetch(ajaxurl, { method: 'POST', body: data })
        .then(function (response) { return response.json(); })
        .then(function () { location.reload(); });
});
</script>
