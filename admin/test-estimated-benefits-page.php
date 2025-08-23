<?php
/**
 * Test Estimated Benefits admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$categories = RTBCB_Category_Recommender::get_all_categories();
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Test Estimated Benefits', 'rtbcb' ); ?></h1>

    <div class="card">
        <h2 class="title"><?php esc_html_e( 'Generate Benefits Estimate', 'rtbcb' ); ?></h2>
        <p><?php esc_html_e( 'Enter your company data to generate an AI-powered benefits estimate.', 'rtbcb' ); ?></p>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="rtbcb-test-revenue"><?php esc_html_e( 'Annual Revenue', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <input type="number" id="rtbcb-test-revenue" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. 5000000', 'rtbcb' ); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb-test-staff-count"><?php esc_html_e( 'Staff Count', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <input type="number" id="rtbcb-test-staff-count" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. 25', 'rtbcb' ); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb-test-efficiency"><?php esc_html_e( 'Current Efficiency', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <input type="range" id="rtbcb-test-efficiency" min="1" max="10" value="5" />
                    <span id="rtbcb-efficiency-value">5</span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb-test-category"><?php esc_html_e( 'Solution Category', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <select id="rtbcb-test-category">
                        <option value=""><?php esc_html_e( 'Select category', 'rtbcb' ); ?></option>
                        <?php foreach ( $categories as $key => $cat_info ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $cat_info['label'] ?? $key ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php wp_nonce_field( 'rtbcb_test_estimated_benefits', 'rtbcb_test_estimated_benefits_nonce' ); ?>
        <p class="submit">
            <button type="button" id="rtbcb-generate-benefits" class="button button-primary"><?php esc_html_e( 'Generate Estimate', 'rtbcb' ); ?></button>
            <button type="button" id="rtbcb-clear-benefits" class="button"><?php esc_html_e( 'Clear Results', 'rtbcb' ); ?></button>
            <button type="button" id="rtbcb-copy-benefits" class="button"><?php esc_html_e( 'Copy Results', 'rtbcb' ); ?></button>
        </p>
    </div>

    <div id="rtbcb-estimated-benefits-results"></div>
</div>

<style>
#rtbcb-estimated-benefits-results {
    margin-top: 20px;
}

#rtbcb-estimated-benefits-results .notice {
    margin: 5px 0;
}

#rtbcb-estimated-benefits-results div[style*="background"] {
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
