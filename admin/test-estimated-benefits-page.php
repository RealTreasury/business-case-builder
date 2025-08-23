<?php
/**
 * Test Estimated Benefits admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Test Estimated Benefits', 'rtbcb' ); ?></h1>

    <div class="card">
        <h2 class="title"><?php esc_html_e( 'Generate Estimated Benefits', 'rtbcb' ); ?></h2>

        <form id="rtbcb-benefits-estimate-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="rtbcb-test-revenue"><?php esc_html_e( 'Company Annual Revenue', 'rtbcb' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="rtbcb-test-revenue" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="rtbcb-test-staff-count"><?php esc_html_e( 'Treasury Staff Count', 'rtbcb' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="rtbcb-test-staff-count" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="rtbcb-test-efficiency"><?php esc_html_e( 'Current Process Efficiency', 'rtbcb' ); ?></label>
                    </th>
                    <td>
                        <input type="range" id="rtbcb-test-efficiency" min="1" max="10" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="rtbcb-test-category"><?php esc_html_e( 'Category', 'rtbcb' ); ?></label>
                    </th>
                    <td>
                        <select id="rtbcb-test-category">
                            <?php foreach ( RTBCB_Category_Recommender::get_all_categories() as $key => $category ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>">
                                    <?php echo esc_html( $category['name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php wp_nonce_field( 'rtbcb_test_estimated_benefits', 'rtbcb_test_estimated_benefits_nonce' ); ?>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" id="rtbcb-generate-benefits-estimate" class="button button-primary">
                    <?php esc_html_e( 'Generate Estimate', 'rtbcb' ); ?>
                </button>
            </p>
        </form>

        <div id="rtbcb-benefits-estimate-results"></div>
    </div>
</div>
