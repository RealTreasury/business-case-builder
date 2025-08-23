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
    <h1><?php esc_html_e( 'Treasury Report Section Testing Dashboard', 'rtbcb' ); ?></h1>

    <div class="card">
        <h2 class="title"><?php esc_html_e( 'Test Tools', 'rtbcb' ); ?></h2>
        <ul>
            <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-test-company-overview' ) ); ?>"><?php esc_html_e( 'Test Company Overview', 'rtbcb' ); ?></a></li>
            <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-test-treasury-tech-overview' ) ); ?>"><?php esc_html_e( 'Test Treasury Tech Overview', 'rtbcb' ); ?></a></li>
            <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-test-industry-overview' ) ); ?>"><?php esc_html_e( 'Test Industry Overview', 'rtbcb' ); ?></a></li>
            <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-report-preview' ) ); ?>"><?php esc_html_e( 'Report Preview', 'rtbcb' ); ?></a></li>
            <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-api-test' ) ); ?>"><?php esc_html_e( 'API Test', 'rtbcb' ); ?></a></li>
        </ul>
        <p class="submit">
            <button type="button" id="rtbcb-test-all-sections" class="button button-primary">
                <?php esc_html_e( 'Test All Sections', 'rtbcb' ); ?>
            </button>
            <?php wp_nonce_field( 'rtbcb_test_dashboard', 'rtbcb_test_dashboard_nonce' ); ?>
        </p>
        <p id="rtbcb-test-status"></p>
    </div>

    <div class="card">
        <h2 class="title"><?php esc_html_e( 'Recent Test Results', 'rtbcb' ); ?></h2>
        <table class="widefat striped" id="rtbcb-test-results-summary">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Section', 'rtbcb' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'rtbcb' ); ?></th>
                    <th><?php esc_html_e( 'Message', 'rtbcb' ); ?></th>
                    <th><?php esc_html_e( 'Timestamp', 'rtbcb' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if ( ! empty( $test_results ) ) : ?>
                <?php foreach ( $test_results as $result ) : ?>
                    <tr>
                        <td><?php echo esc_html( $result['section'] ); ?></td>
                        <td><?php echo esc_html( $result['status'] ); ?></td>
                        <td><?php echo esc_html( $result['message'] ); ?></td>
                        <td><?php echo esc_html( $result['timestamp'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4"><?php esc_html_e( 'No test results found.', 'rtbcb' ); ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2 class="title"><?php esc_html_e( 'System Status', 'rtbcb' ); ?></h2>
        <table class="widefat striped">
            <tbody>
                <tr>
                    <th><?php esc_html_e( 'OpenAI API Key', 'rtbcb' ); ?></th>
                    <td><?php echo $openai_status ? esc_html__( 'Configured', 'rtbcb' ) : esc_html__( 'Missing', 'rtbcb' ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Portal Integration', 'rtbcb' ); ?></th>
                    <td><?php echo $portal_active ? esc_html__( 'Active', 'rtbcb' ) : esc_html__( 'Inactive', 'rtbcb' ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'RAG Index', 'rtbcb' ); ?></th>
                    <td><?php echo $rag_health ? esc_html__( 'Healthy', 'rtbcb' ) : esc_html__( 'Needs attention', 'rtbcb' ); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
