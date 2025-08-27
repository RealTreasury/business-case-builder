<?php
/**
 * Test Dashboard admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$company    = rtbcb_get_current_company();
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';

$tabs = [
    'overview'              => __( 'Overview', 'rtbcb' ),
    'company-overview'      => __( 'Company Overview', 'rtbcb' ),
    'treasury-tech-overview'=> __( 'Treasury Tech Overview', 'rtbcb' ),
    'industry-overview'     => __( 'Industry Overview', 'rtbcb' ),
    'real-treasury-overview'=> __( 'Real Treasury Overview', 'rtbcb' ),
    'recommended-category'  => __( 'Recommended Category', 'rtbcb' ),
    'estimated-benefits'    => __( 'Estimated Benefits', 'rtbcb' ),
    'api-test'              => __( 'API Test', 'rtbcb' ),
    'report-preview'        => __( 'Report Preview', 'rtbcb' ),
    'report-test'           => __( 'Report Test', 'rtbcb' ),
];
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Treasury Report Section Testing Dashboard', 'rtbcb' ); ?></h1>
    <?php rtbcb_render_start_new_analysis_button(); ?>

    <h2 class="nav-tab-wrapper">
        <?php foreach ( $tabs as $slug => $label ) : ?>
            <?php
            $url          = admin_url( 'admin.php?page=rtbcb-test-dashboard&tab=' . $slug );
            $active_class = $active_tab === $slug ? ' nav-tab-active' : '';
            ?>
            <a href="<?php echo esc_url( $url ); ?>" class="nav-tab<?php echo esc_attr( $active_class ); ?>">
                <?php echo esc_html( $label ); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <div class="rtbcb-tab-content">
        <?php
        switch ( $active_tab ) {
            case 'company-overview':
                include RTBCB_DIR . 'admin/partials/test-company-overview.php';
                break;
            case 'treasury-tech-overview':
                include RTBCB_DIR . 'admin/partials/test-treasury-tech-overview.php';
                break;
            case 'industry-overview':
                include RTBCB_DIR . 'admin/partials/test-industry-overview.php';
                break;
            case 'real-treasury-overview':
                include RTBCB_DIR . 'admin/partials/test-real-treasury-overview.php';
                break;
            case 'recommended-category':
                include RTBCB_DIR . 'admin/partials/test-recommended-category.php';
                break;
            case 'estimated-benefits':
                include RTBCB_DIR . 'admin/partials/test-estimated-benefits.php';
                break;
            case 'api-test':
                include RTBCB_DIR . 'admin/partials/api-test.php';
                break;
            case 'report-preview':
                include RTBCB_DIR . 'admin/partials/report-preview.php';
                break;
            case 'report-test':
                include RTBCB_DIR . 'admin/partials/report-test.php';
                break;
            case 'overview':
            default:
                $steps = rtbcb_get_test_steps();
                ?>
                <div class="card">
                    <h3 class="title"><?php esc_html_e( 'Analysis Progress', 'rtbcb' ); ?></h3>
                    <ul>
                        <?php foreach ( $steps as $slug => $step ) : ?>
                            <?php $done = ! empty( get_option( $step['option'] ) ); ?>
                            <li class="<?php echo $done ? 'completed' : 'missing'; ?>">
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rtbcb-test-dashboard&tab=' . str_replace( 'rtbcb-test-', '', $slug ) ) ); ?>">
                                    <?php echo esc_html( $step['label'] ); ?>
                                </a>
                                - <?php echo $done ? esc_html__( 'Complete', 'rtbcb' ) : esc_html__( 'Incomplete', 'rtbcb' ); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card">
                    <h3 class="title"><?php esc_html_e( 'Test Tools', 'rtbcb' ); ?></h3>
                    <p class="submit">
                        <button type="button" id="rtbcb-test-all-sections" class="button button-primary">
                            <?php esc_html_e( 'Test All Sections', 'rtbcb' ); ?>
                        </button>
                        <?php wp_nonce_field( 'rtbcb_test_dashboard', 'rtbcb_test_dashboard_nonce' ); ?>
                    </p>
                    <p id="rtbcb-test-status"></p>
                </div>
                <div class="card">
                    <h3 class="title"><?php esc_html_e( 'Recent Test Results', 'rtbcb' ); ?></h3>
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
                    <h3 class="title"><?php esc_html_e( 'System Status', 'rtbcb' ); ?></h3>
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
                <?php
                break;
        }
        ?>
    </div>
</div>
