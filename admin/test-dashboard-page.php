<?php
/**
 * Test Dashboard admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$company_data = get_option( 'rtbcb_company_data', [] );
$company_name = isset( $company_data['name'] ) ? sanitize_text_field( $company_data['name'] ) : '';
?>
<div class="wrap rtbcb-admin-page">
    <h1><?php esc_html_e( 'Treasury Report Section Testing Dashboard', 'rtbcb' ); ?></h1>
    <?php rtbcb_render_start_new_analysis_button(); ?>

    <?php
    $sections = rtbcb_get_dashboard_sections();
    $phases   = [
        1 => __( 'Phase 1: Data Collection & Enrichment', 'rtbcb' ),
        2 => __( 'Phase 2: Analysis & Content Generation', 'rtbcb' ),
        3 => __( 'Phase 3: ROI & Strategic Recommendation', 'rtbcb' ),
        4 => __( 'Phase 4: Report Assembly & Delivery', 'rtbcb' ),
        5 => __( 'Phase 5: Post-Delivery Engagement', 'rtbcb' ),
    ];
    $sections_by_phase = [];
    foreach ( $sections as $id => $section ) {
        $phase = isset( $section['phase'] ) ? (int) $section['phase'] : 0;
        if ( $phase ) {
            $sections_by_phase[ $phase ][ $id ] = $section;
        }
    }
    ?>
    <div class="card">
        <h2 class="title"><?php esc_html_e( 'Analysis Progress', 'rtbcb' ); ?></h2>
        <ul>
            <?php foreach ( $phases as $phase_num => $phase_label ) : ?>
                <li>
                    <strong><?php echo esc_html( $phase_label ); ?></strong>
                    <?php if ( ! empty( $sections_by_phase[ $phase_num ] ) ) : ?>
                        <ul>
                            <?php foreach ( $sections_by_phase[ $phase_num ] as $id => $section ) : ?>
                                <?php $done = ! empty( $section['completed'] ); ?>
                                <li class="<?php echo $done ? 'completed' : 'missing'; ?>">
                                    <a href="#rtbcb-phase<?php echo esc_attr( $phase_num ); ?>" class="rtbcb-jump-tab">
                                        <?php echo esc_html( $section['label'] ); ?>
                                    </a>
                                    - <?php echo $done ? esc_html__( 'Complete', 'rtbcb' ) : esc_html__( 'Incomplete', 'rtbcb' ); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        - <?php esc_html_e( 'No tests yet.', 'rtbcb' ); ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="card">
        <h2 class="title"><?php esc_html_e( 'Test Tools', 'rtbcb' ); ?></h2>
        <p>
            <label for="rtbcb-company-name"><?php esc_html_e( 'Company Name', 'rtbcb' ); ?></label>
            <input type="text" id="rtbcb-company-name" class="regular-text" value="<?php echo esc_attr( $company_name ); ?>" />
            <button type="button" id="rtbcb-set-company" class="button"><?php esc_html_e( 'Set Company', 'rtbcb' ); ?></button>
            <?php wp_nonce_field( 'rtbcb_set_test_company', 'rtbcb_set_test_company_nonce' ); ?>
        </p>
        <p class="submit">
            <button type="button" id="rtbcb-test-all-sections" class="button button-primary">
                <?php esc_html_e( 'Test All Sections', 'rtbcb' ); ?>
            </button>
            <?php wp_nonce_field( 'rtbcb_test_dashboard', 'rtbcb_test_dashboard_nonce' ); ?>
        </p>
        <p id="rtbcb-test-status"></p>
    </div>

    <?php include RTBCB_DIR . 'admin/partials/dashboard-connectivity.php'; ?>

    <h2 class="nav-tab-wrapper" id="rtbcb-test-tabs">
        <a href="#rtbcb-phase1" class="nav-tab nav-tab-active"><?php echo esc_html( $phases[1] ); ?></a>
        <a href="#rtbcb-phase2" class="nav-tab"><?php echo esc_html( $phases[2] ); ?></a>
        <a href="#rtbcb-phase3" class="nav-tab"><?php echo esc_html( $phases[3] ); ?></a>
        <a href="#rtbcb-phase4" class="nav-tab"><?php echo esc_html( $phases[4] ); ?></a>
        <a href="#rtbcb-phase5" class="nav-tab"><?php echo esc_html( $phases[5] ); ?></a>
    </h2>

    <div id="rtbcb-phase1" class="rtbcb-tab-panel" style="display:block;">
        <?php include RTBCB_DIR . 'admin/partials/test-company-overview.php'; ?>
        <?php include RTBCB_DIR . 'admin/partials/test-data-enrichment.php'; ?>
        <?php include RTBCB_DIR . 'admin/partials/test-data-storage.php'; ?>
    </div>
    <div id="rtbcb-phase2" class="rtbcb-tab-panel" style="display:none;">
        <?php include RTBCB_DIR . 'admin/partials/test-maturity-model.php'; ?>
        <?php include RTBCB_DIR . 'admin/partials/test-rag-market-analysis.php'; ?>
        <?php include RTBCB_DIR . 'admin/partials/test-value-proposition.php'; ?>
        <?php include RTBCB_DIR . 'admin/partials/test-industry-overview.php'; ?>
        <?php include RTBCB_DIR . 'admin/partials/test-real-treasury-overview.php'; ?>
    </div>
    <div id="rtbcb-phase3" class="rtbcb-tab-panel" style="display:none;">
        <?php include RTBCB_DIR . 'admin/partials/test-recommended-category.php'; ?>
        <?php include RTBCB_DIR . 'admin/partials/test-estimated-benefits.php'; ?>
    </div>
    <div id="rtbcb-phase4" class="rtbcb-tab-panel" style="display:none;">
        <?php include RTBCB_DIR . 'admin/partials/test-report-assembly.php'; ?>
    </div>
    <div id="rtbcb-phase5" class="rtbcb-tab-panel" style="display:none;">
        <?php include RTBCB_DIR . 'admin/partials/test-post-delivery.php'; ?>
    </div>

    <script>
    // Tabs handled in admin/js/rtbcb-admin.js
    </script>
</div>
