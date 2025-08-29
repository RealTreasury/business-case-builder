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
$overview_url = admin_url( 'admin.php?page=rtbcb-test-dashboard#rtbcb-phase1' );
    echo '<div class="notice notice-error"><p>' . sprintf(
        esc_html__( 'No company data found. Please run the %s first.', 'rtbcb' ),
        '<a href="' . esc_url( $overview_url ) . '">' . esc_html__( 'Company Overview', 'rtbcb' ) . '</a>'
    ) . '</p></div>';
    return;
}

$company_name = isset( $company['name'] ) ? sanitize_text_field( $company['name'] ) : '';
$company_sum  = isset( $company['summary'] ) ? sanitize_textarea_field( $company['summary'] ) : '';
$company_ind  = isset( $company['industry'] ) ? sanitize_text_field( $company['industry'] ) : '';
$company_size = isset( $company['size'] ) ? sanitize_text_field( $company['size'] ) : '';
?>
<h2><?php esc_html_e( 'Test Industry Overview', 'rtbcb' ); ?></h2>
<p class="description"><?php esc_html_e( 'Generate insights about the company\'s industry to inform later recommendations.', 'rtbcb' ); ?></p>
<p class="rtbcb-data-source">
    <span class="rtbcb-data-status rtbcb-status-industry-analysis">⚪ <?php esc_html_e( 'Generate new', 'rtbcb' ); ?></span>
    <a href="#rtbcb-comprehensive-analysis" class="rtbcb-view-source" style="display:none;">
        <?php esc_html_e( 'View Source Data', 'rtbcb' ); ?>
    </a>
</p>
<?php $rtbcb_last = rtbcb_get_last_test_result( 'rtbcb-test-industry-overview', $test_results ?? [] ); ?>
<?php if ( $rtbcb_last ) : ?>
    <div class="notice notice-info" role="status">
        <p><strong><?php esc_html_e( 'Status:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['status'] ); ?></p>
        <p><strong><?php esc_html_e( 'Message:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['message'] ); ?></p>
        <p><strong><?php esc_html_e( 'Timestamp:', 'rtbcb' ); ?></strong> <?php echo esc_html( $rtbcb_last['timestamp'] ); ?></p>
        <p class="submit">
            <button type="button" class="button" id="rtbcb-rerun-industry-overview" data-section="rtbcb-test-industry-overview">
                <?php esc_html_e( 'Regenerate This Section Only', 'rtbcb' ); ?>
            </button>
        </p>
    </div>
<?php endif; ?>
<?php if ( ! empty( $company_name ) ) : ?>
    <h3><?php echo esc_html( $company_name ); ?></h3>
    <?php if ( ! empty( $company_sum ) ) : ?>
        <p><?php echo esc_html( $company_sum ); ?></p>
    <?php endif; ?>
<?php endif; ?>
<form id="rtbcb-industry-overview-form">
    <input type="hidden" id="rtbcb-company-size" value="<?php echo esc_attr( $company_size ); ?>" />
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
<div id="rtbcb-industry-overview-card" class="rtbcb-result-card">
    <details>
        <summary><?php esc_html_e( 'Generated Overview', 'rtbcb' ); ?></summary>
        <div id="<?php echo esc_attr( 'rtbcb-industry-overview-results' ); ?>">
            <div id="<?php echo esc_attr( 'rtbcb-industry-overview-meta' ); ?>" class="rtbcb-meta"></div>
        </div>
    </details>
</div>
<script>
document.getElementById( 'rtbcb-rerun-industry-overview' )?.addEventListener( 'click', function() {
    jQuery( '#rtbcb-industry-overview-form' ).trigger( 'submit' );
});
</script>
