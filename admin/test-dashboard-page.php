<?php
/**
 * Test Dashboard admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$company = rtbcb_get_current_company();
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Treasury Report Section Testing Dashboard', 'rtbcb' ); ?></h1>
    <?php rtbcb_render_start_new_analysis_button(); ?>

    <?php
    $steps = rtbcb_get_test_steps();
    ?>
    <div class="card">
        <h2 class="title"><?php esc_html_e( 'Analysis Progress', 'rtbcb' ); ?></h2>
        <ul>
            <?php foreach ( $steps as $slug => $step ) : ?>
                <?php $done = ! empty( get_option( $step['option'] ) ); ?>
                <li class="<?php echo $done ? 'completed' : 'missing'; ?>">
                    <a href="#<?php echo esc_attr( $slug ); ?>">
                        <?php echo esc_html( $step['label'] ); ?>
                    </a>
                    - <?php echo $done ? esc_html__( 'Complete', 'rtbcb' ) : esc_html__( 'Incomplete', 'rtbcb' ); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="card">
        <h2 class="title"><?php esc_html_e( 'Test Tools', 'rtbcb' ); ?></h2>
        <p class="submit">
            <button type="button" id="rtbcb-test-all-sections" class="button button-primary">
                <?php esc_html_e( 'Test All Sections', 'rtbcb' ); ?>
            </button>
            <?php wp_nonce_field( 'rtbcb_test_dashboard', 'rtbcb_test_dashboard_nonce' ); ?>
        </p>
        <p id="rtbcb-test-status"></p>
    </div>

    <h2 class="nav-tab-wrapper" id="rtbcb-test-tabs">
        <a href="#rtbcb-test-company-overview" class="nav-tab nav-tab-active"><?php esc_html_e( 'Company Overview', 'rtbcb' ); ?></a>
        <a href="#rtbcb-test-treasury-tech-overview" class="nav-tab"><?php esc_html_e( 'Treasury Tech', 'rtbcb' ); ?></a>
        <a href="#rtbcb-test-industry-overview" class="nav-tab"><?php esc_html_e( 'Industry', 'rtbcb' ); ?></a>
        <a href="#rtbcb-test-real-treasury-overview" class="nav-tab"><?php esc_html_e( 'Real Treasury', 'rtbcb' ); ?></a>
        <a href="#rtbcb-test-recommended-category" class="nav-tab"><?php esc_html_e( 'Recommended Category', 'rtbcb' ); ?></a>
        <a href="#rtbcb-test-estimated-benefits" class="nav-tab"><?php esc_html_e( 'Estimated Benefits', 'rtbcb' ); ?></a>
        <a href="#rtbcb-api-test" class="nav-tab"><?php esc_html_e( 'API Test', 'rtbcb' ); ?></a>
        <a href="#rtbcb-report-preview" class="nav-tab"><?php esc_html_e( 'Report Preview', 'rtbcb' ); ?></a>
        <a href="#rtbcb-report-test" class="nav-tab"><?php esc_html_e( 'Report Test', 'rtbcb' ); ?></a>
    </h2>

    <div id="rtbcb-test-company-overview" class="rtbcb-tab-panel" style="display:block;">
        <?php include RTBCB_DIR . 'admin/partials/test-company-overview.php'; ?>
    </div>
    <div id="rtbcb-test-treasury-tech-overview" class="rtbcb-tab-panel" style="display:none;">
        <?php include RTBCB_DIR . 'admin/partials/test-treasury-tech-overview.php'; ?>
    </div>
    <div id="rtbcb-test-industry-overview" class="rtbcb-tab-panel" style="display:none;">
        <?php include RTBCB_DIR . 'admin/partials/test-industry-overview.php'; ?>
    </div>
    <div id="rtbcb-test-real-treasury-overview" class="rtbcb-tab-panel" style="display:none;">
        <?php include RTBCB_DIR . 'admin/partials/test-real-treasury-overview.php'; ?>
    </div>
    <div id="rtbcb-test-recommended-category" class="rtbcb-tab-panel" style="display:none;">
        <?php include RTBCB_DIR . 'admin/partials/test-recommended-category.php'; ?>
    </div>
    <div id="rtbcb-test-estimated-benefits" class="rtbcb-tab-panel" style="display:none;">
        <?php include RTBCB_DIR . 'admin/partials/test-estimated-benefits.php'; ?>
    </div>
    <div id="rtbcb-api-test" class="rtbcb-tab-panel" style="display:none;">
        <?php include RTBCB_DIR . 'admin/partials/test-api.php'; ?>
    </div>
    <div id="rtbcb-report-preview" class="rtbcb-tab-panel" style="display:none;">
        <?php include RTBCB_DIR . 'admin/partials/test-report-preview.php'; ?>
    </div>
    <div id="rtbcb-report-test" class="rtbcb-tab-panel" style="display:none;">
        <?php include RTBCB_DIR . 'admin/partials/test-report.php'; ?>
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
    <script>
    jQuery(function($){
        $('#rtbcb-test-tabs a').on('click', function(e){
            e.preventDefault();
            var target = $(this).attr('href');
            $('#rtbcb-test-tabs a').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            $('.rtbcb-tab-panel').hide();
            $(target).show();
        });
    });
    </script>
</div>
