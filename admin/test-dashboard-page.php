<?php
/**
 * Test dashboard page for Treasury Report sections.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Treasury Report Section Testing Dashboard', 'rtbcb' ); ?></h1>

    <p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-test-company-overview' ) ); ?>"><?php esc_html_e( 'Company Overview Test', 'rtbcb' ); ?></a> |
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-test-treasury-tech-overview' ) ); ?>"><?php esc_html_e( 'Treasury Tech Overview Test', 'rtbcb' ); ?></a> |
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-report-preview' ) ); ?>"><?php esc_html_e( 'Report Preview', 'rtbcb' ); ?></a> |
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-report-test' ) ); ?>"><?php esc_html_e( 'Report Test', 'rtbcb' ); ?></a>
    </p>

    <p>
        <button id="rtbcb-test-all" class="button button-primary"><?php esc_html_e( 'Test All Sections', 'rtbcb' ); ?></button>
    </p>

    <h2><?php esc_html_e( 'Recent Test Results', 'rtbcb' ); ?></h2>
    <table class="widefat" id="rtbcb-test-summary">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Time', 'rtbcb' ); ?></th>
                <th><?php esc_html_e( 'Section', 'rtbcb' ); ?></th>
                <th><?php esc_html_e( 'Status', 'rtbcb' ); ?></th>
                <th><?php esc_html_e( 'Message', 'rtbcb' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! empty( $results ) ) : ?>
                <?php foreach ( array_reverse( $results ) as $result ) : ?>
                    <tr>
                        <td><?php echo esc_html( $result['time'] ); ?></td>
                        <td><?php echo esc_html( $result['section'] ); ?></td>
                        <td><?php echo esc_html( $result['status'] ); ?></td>
                        <td><?php echo esc_html( $result['message'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4"><?php esc_html_e( 'No test results found.', 'rtbcb' ); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h2><?php esc_html_e( 'System Status', 'rtbcb' ); ?></h2>
    <ul>
        <li>
            <?php esc_html_e( 'OpenAI API Key:', 'rtbcb' ); ?>
            <?php echo $system_status['openai'] ? '<span class="status-ok">' . esc_html__( 'Configured', 'rtbcb' ) . '</span>' : '<span class="status-error">' . esc_html__( 'Missing', 'rtbcb' ) . '</span>'; ?>
        </li>
        <li>
            <?php esc_html_e( 'Portal Integration:', 'rtbcb' ); ?>
            <?php echo $system_status['portal'] ? '<span class="status-ok">' . esc_html__( 'Active', 'rtbcb' ) . '</span>' : '<span class="status-error">' . esc_html__( 'Inactive', 'rtbcb' ) . '</span>'; ?>
        </li>
        <li>
            <?php esc_html_e( 'RAG Index:', 'rtbcb' ); ?>
            <?php echo ( isset( $system_status['rag']['status'] ) && 'healthy' === $system_status['rag']['status'] ) ? '<span class="status-ok">' . esc_html__( 'Healthy', 'rtbcb' ) . '</span>' : '<span class="status-error">' . esc_html__( 'Needs Rebuild', 'rtbcb' ) . '</span>'; ?>
        </li>
    </ul>
</div>
