<?php
/**
 * Testing dashboard admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$memory = rtbcb_get_memory_status();
$api_key = get_option( 'rtbcb_openai_api_key' );
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Testing Dashboard', 'rtbcb' ); ?></h1>

    <p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-api-test' ) ); ?>"><?php esc_html_e( 'API Test', 'rtbcb' ); ?></a> |
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-report-test' ) ); ?>"><?php esc_html_e( 'Report Test', 'rtbcb' ); ?></a> |
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-calculations' ) ); ?>"><?php esc_html_e( 'Calculation Info', 'rtbcb' ); ?></a> |
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-test-company-overview' ) ); ?>"><?php esc_html_e( 'Company Overview', 'rtbcb' ); ?></a>
    </p>

    <div class="card">
        <h2 class="title"><?php esc_html_e( 'System Status', 'rtbcb' ); ?></h2>
        <ul>
            <li><?php esc_html_e( 'API Key:', 'rtbcb' ); ?> <?php echo $api_key ? esc_html__( 'Configured', 'rtbcb' ) : esc_html__( 'Not Configured', 'rtbcb' ); ?></li>
            <li><?php esc_html_e( 'Memory Usage:', 'rtbcb' ); ?> <?php echo esc_html( size_format( $memory['usage'] ) ); ?> / <?php echo esc_html( $memory['limit'] ); ?></li>
        </ul>
    </div>

    <p class="submit">
        <button type="button" id="rtbcb-test-all" class="button button-primary"><?php esc_html_e( 'Test All Sections', 'rtbcb' ); ?></button>
        <button type="button" id="rtbcb-clear-all" class="button"><?php esc_html_e( 'Clear', 'rtbcb' ); ?></button>
        <button type="button" id="rtbcb-copy-report" class="button"><?php esc_html_e( 'Copy Report', 'rtbcb' ); ?></button>
    </p>

    <div id="rtbcb-test-dashboard-results"></div>
</div>
