<?php
/**
 * Test Dashboard admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Test Dashboard', 'rtbcb' ); ?></h1>

    <h2><?php esc_html_e( 'System Status', 'rtbcb' ); ?></h2>
    <ul>
        <li>
            <?php
            echo esc_html(
                rtbcb_is_configured()
                    ? __( 'OpenAI API configured', 'rtbcb' )
                    : __( 'OpenAI API not configured', 'rtbcb' )
            );
            ?>
        </li>
    </ul>

    <h2><?php esc_html_e( 'Test Tools', 'rtbcb' ); ?></h2>
    <p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-test-company-overview' ) ); ?>" class="button">
            <?php esc_html_e( 'Company Overview Test', 'rtbcb' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-test-industry-overview' ) ); ?>" class="button">
            <?php esc_html_e( 'Industry Overview Test', 'rtbcb' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-test-treasury-tech-overview' ) ); ?>" class="button">
            <?php esc_html_e( 'Treasury Tech Overview Test', 'rtbcb' ); ?>
        </a>
    </p>

    <p class="submit">
        <button type="button" id="rtbcb-test-all" class="button button-primary">
            <?php esc_html_e( 'Test All Sections', 'rtbcb' ); ?>
        </button>
        <?php wp_nonce_field( 'rtbcb_test_complete_report', 'rtbcb_test_complete_report_nonce' ); ?>
    </p>

    <div id="rtbcb-test-dashboard-status"></div>

    <div id="rtbcb-complete-report" style="display:none;">
        <h2><?php esc_html_e( 'Combined Report', 'rtbcb' ); ?></h2>
        <div id="rtbcb-complete-report-content"></div>
        <p id="rtbcb-complete-report-meta"></p>
        <p class="submit">
            <button type="button" id="rtbcb-regenerate-report" class="button">
                <?php esc_html_e( 'Regenerate', 'rtbcb' ); ?>
            </button>
            <button type="button" id="rtbcb-copy-report" class="button">
                <?php esc_html_e( 'Copy', 'rtbcb' ); ?>
            </button>
            <button type="button" id="rtbcb-clear-report" class="button">
                <?php esc_html_e( 'Clear', 'rtbcb' ); ?>
            </button>
        </p>
    </div>
</div>

<script>
// Ensure ajaxurl is available
<?php if ( ! isset( $GLOBALS['ajaxurl'] ) || empty( $GLOBALS['ajaxurl'] ) ) : ?>
var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
<?php endif; ?>
</script>
