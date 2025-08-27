<?php
/**
 * Partial for Test Industry Overview section.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$allowed = rtbcb_require_completed_steps( 'rtbcb-test-industry-overview' );
if ( ! $allowed ) {
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

$company_name = isset( $company['name'] ) ? sanitize_text_field( $company['name'] ) : '';
$company_sum  = isset( $company['summary'] ) ? sanitize_textarea_field( $company['summary'] ) : '';
$company_ind  = isset( $company['industry'] ) ? sanitize_text_field( $company['industry'] ) : '';
?>
<h2><?php esc_html_e( 'Test Industry Overview', 'rtbcb' ); ?></h2>
<?php if ( ! empty( $company_name ) ) : ?>
    <h3><?php echo esc_html( $company_name ); ?></h3>
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
