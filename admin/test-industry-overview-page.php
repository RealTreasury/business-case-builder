<?php
/**
 * Test Industry Overview admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$company       = function_exists( 'rtbcb_get_current_company' ) ? rtbcb_get_current_company() : [];
$company_name  = isset( $company['name'] ) ? sanitize_text_field( $company['name'] ) : '';
$company_sum   = isset( $company['summary'] ) ? sanitize_textarea_field( $company['summary'] ) : '';
$company_ind   = isset( $company['industry'] ) ? sanitize_text_field( $company['industry'] ) : '';
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Test Industry Overview', 'rtbcb' ); ?></h1>

    <?php if ( ! empty( $company_name ) ) : ?>
        <h2><?php echo esc_html( $company_name ); ?></h2>
        <?php if ( ! empty( $company_sum ) ) : ?>
            <p><?php echo esc_html( $company_sum ); ?></p>
        <?php endif; ?>
    <?php endif; ?>

    <form id="rtbcb-industry-overview-form">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="rtbcb-industry-name"><?php esc_html_e( 'Industry Name', 'rtbcb' ); ?></label>
                </th>
                <td>
                    <input type="text" id="rtbcb-industry-name" class="regular-text" value="<?php echo esc_attr( $company_ind ); ?>" />
                    <p class="description"><?php esc_html_e( 'Override if needed.', 'rtbcb' ); ?></p>
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
