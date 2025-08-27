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
    $sections = rtbcb_get_dashboard_sections();
    ?>
    <div class="card">
        <h2 class="title"><?php esc_html_e( 'Analysis Progress', 'rtbcb' ); ?></h2>
        <ul>
            <?php foreach ( $sections as $id => $section ) : ?>
                <?php $done = ! empty( $section['completed'] ); ?>
                <li class="<?php echo $done ? 'completed' : 'missing'; ?>">
                    <a href="#<?php echo esc_attr( $id ); ?>">
                        <?php echo esc_html( $section['label'] ); ?>
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

    <?php
    $rag_is_healthy = isset( $rag_health['status'] ) && 'healthy' === $rag_health['status'];
    include RTBCB_DIR . 'admin/partials/test-dashboard-connectivity-card.php';
    ?>

    <?php include RTBCB_DIR . 'admin/partials/test-dashboard-nav-tabs.php'; ?>

    <?php include RTBCB_DIR . 'admin/partials/test-dashboard-tab-panels.php'; ?>

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
