<?php
/**
 * Test Industry Overview admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Test Industry Overview', 'rtbcb' ); ?></h1>

    <form id="rtbcb-industry-overview-form">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="rtbcb-industry-name"><?php esc_html_e( 'Industry Name', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <input type="text" id="rtbcb-industry-name" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. Manufacturing', 'rtbcb' ); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rtbcb-company-size"><?php esc_html_e( 'Company Size', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <input type="text" id="rtbcb-company-size" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. 100-500', 'rtbcb' ); ?>" />
                    <?php wp_nonce_field( 'rtbcb_test_industry_overview', 'nonce' ); ?>
                </td>
            </tr>
        </table>
        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Generate Overview', 'rtbcb' ); ?></button>
            <button type="button" id="rtbcb-clear-results" class="button"><?php esc_html_e( 'Clear Results', 'rtbcb' ); ?></button>
        </p>
    </form>

    <div id="rtbcb-industry-overview-results"></div>
</div>
