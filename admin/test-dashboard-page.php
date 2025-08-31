<?php
/**
	* Test Dashboard admin page.
	*
	* @package RealTreasuryBusinessCaseBuilder
	*/
defined( 'ABSPATH' ) || exit;
$company_data   = get_option( 'rtbcb_company_data', [] );
$company_name   = isset( $company_data['name'] ) ? sanitize_text_field( $company_data['name'] ) : '';
$test_results  = get_option( 'rtbcb_test_results', [] );
$sections      = rtbcb_get_dashboard_sections( $test_results );
$total_sections = count( $sections );
?>
<div class="wrap rtbcb-admin-page">
	<h1><?php esc_html_e( 'Treasury Report Section Testing Dashboard', 'rtbcb' ); ?></h1>
	<?php rtbcb_render_start_new_analysis_button(); ?>
	<div class="card">
		<h2 class="title"><?php esc_html_e( 'Run All Tests', 'rtbcb' ); ?></h2>
		<p>
			<label for="rtbcb-company-name"><?php esc_html_e( 'Company Name', 'rtbcb' ); ?></label>
			<input type="text" id="rtbcb-company-name" class="regular-text" value="<?php echo esc_attr( $company_name ); ?>" />
			<button type="button" id="rtbcb-set-company" class="button"><?php esc_html_e( 'Set Company', 'rtbcb' ); ?></button>
			<?php wp_nonce_field( 'rtbcb_set_test_company', 'rtbcb_set_test_company_nonce' ); ?>
		</p>
		<p class="description"><?php esc_html_e( 'Run the complete test suite. Individual test tools will be available after completion.', 'rtbcb' ); ?></p>
		<p class="submit">
			<button type="button" id="rtbcb-test-all-sections" class="button button-primary">
				<?php esc_html_e( 'Run All Tests', 'rtbcb' ); ?>
			</button>
			<?php wp_nonce_field( 'rtbcb_test_dashboard', 'rtbcb_test_dashboard_nonce' ); ?>
		</p>
		<progress id="rtbcb-test-progress" class="rtbcb-test-progress" max="<?php echo esc_attr( $total_sections ); ?>" value="0" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( $total_sections ); ?>" aria-valuenow="0" aria-label="<?php esc_attr_e( 'Test progress', 'rtbcb' ); ?>"></progress>
		<p id="rtbcb-test-status" role="status" aria-live="polite"></p>
		<p id="rtbcb-test-step"></p>
		<pre id="rtbcb-test-config" class="rtbcb-config-snippet"></pre>
	</div>

	<div id="rtbcb-comprehensive-analysis" class="card" style="display:none;">
		<h2 class="title"><?php esc_html_e( 'Comprehensive Analysis Results', 'rtbcb' ); ?></h2>
		<div id="rtbcb-comprehensive-results"></div>
		<div id="rtbcb-usage-map-wrapper" style="display:none;">
			<table id="rtbcb-usage-map"></table>
		</div>
		<p class="rtbcb-actions">
			<button type="button" id="rtbcb-regenerate-analysis" class="button">
				<?php esc_html_e( 'Regenerate Full Analysis', 'rtbcb' ); ?>
			</button>
			<button type="button" id="rtbcb-export-analysis" class="button">
				<?php esc_html_e( 'Export Results', 'rtbcb' ); ?>
			</button>
			<button type="button" id="rtbcb-show-usage-map" class="button">
				<?php esc_html_e( 'Show Usage Map', 'rtbcb' ); ?>
			</button>
			<button type="button" id="rtbcb-clear-analysis" class="button">
				<?php esc_html_e( 'Clear All Stored Data', 'rtbcb' ); ?>
			</button>
		</p>
	</div>

	<?php
	$phases   = [
		1 => [
			'label'       => __( 'Phase 1: Data Collection & Enrichment', 'rtbcb' ),
			'description' => __( 'Gather company information and enrich it for analysis.', 'rtbcb' ),
		],
		2 => [
			'label'       => __( 'Phase 2: Analysis & Content Generation', 'rtbcb' ),
			'description' => __( 'Generate insights and draft report content.', 'rtbcb' ),
		],
		3 => [
			'label'       => __( 'Phase 3: ROI & Strategic Recommendation', 'rtbcb' ),
			'description' => __( 'Build the business case and recommendations.', 'rtbcb' ),
		],
		4 => [
			'label'       => __( 'Phase 4: Report Assembly & Delivery', 'rtbcb' ),
			'description' => __( 'Assemble results into a sharable report.', 'rtbcb' ),
		],
		5 => [
			'label'       => __( 'Phase 5: Post-Delivery Engagement', 'rtbcb' ),
			'description' => __( 'Engage recipients and track interactions.', 'rtbcb' ),
		],
	];
	$sections_by_phase = [];
	foreach ( $sections as $id => $section ) {
		$phase = isset( $section['phase'] ) ? (int) $section['phase'] : 0;
		if ( $phase ) {
			$sections_by_phase[ $phase ][ $id ] = $section;
		}
	}
	$phase_keys        = array_keys( $phases );
	$phase_percentages = rtbcb_calculate_phase_completion( $sections, $phase_keys );
	?>
	<div class="card rtbcb-progress-card">
		<h2 class="title"><?php esc_html_e( 'Analysis Progress', 'rtbcb' ); ?></h2>
		<div id="rtbcb-progress-steps" class="rtbcb-progress-steps">
			<?php foreach ( $phases as $phase_num => $phase ) : ?>
				<div class="rtbcb-progress-phase" data-phase="<?php echo esc_attr( $phase_num ); ?>">
					<button type="button" class="rtbcb-phase-toggle" aria-expanded="false" aria-controls="rtbcb-phase-content-<?php echo esc_attr( $phase_num ); ?>">
						<span class="rtbcb-phase-label"><?php echo esc_html( $phase['label'] ); ?></span>
						<span class="rtbcb-phase-percent"><?php echo esc_html( $phase_percentages[ $phase_num ] ); ?>%</span>
					</button>
					<div id="rtbcb-phase-content-<?php echo esc_attr( $phase_num ); ?>" class="rtbcb-phase-content">
						<span class="rtbcb-phase-desc"><?php echo esc_html( $phase['description'] ); ?></span>
						<?php if ( ! empty( $sections_by_phase[ $phase_num ] ) ) : ?>
							<?php foreach ( $sections_by_phase[ $phase_num ] as $id => $section ) : ?>
								<?php $done = ! empty( $section['completed'] ); ?>
								<div class="rtbcb-section-item <?php echo $done ? 'completed' : 'pending'; ?>" data-completed="<?php echo $done ? '1' : '0'; ?>">
									<span class="rtbcb-section-label"><?php echo esc_html( $section['label'] ); ?></span>
									<span class="rtbcb-section-status"><?php echo $done ? esc_html__( '✓ Tested', 'rtbcb' ) : esc_html__( '⚪ Pending', 'rtbcb' ); ?></span>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<p class="rtbcb-no-tests"><?php esc_html_e( 'No tests yet.', 'rtbcb' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<script>
	window.rtbcbAdmin = window.rtbcbAdmin || {};
	window.rtbcbAdmin.phaseLabels = <?php echo wp_json_encode( wp_list_pluck( $phases, 'label' ) ); ?>;
	window.rtbcbAdmin.phaseKeys = <?php echo wp_json_encode( $phase_keys ); ?>;
	window.rtbcbAdmin.phaseCompletion = <?php echo wp_json_encode( $phase_percentages ); ?>;
	</script>

	<?php include RTBCB_DIR . 'admin/partials/dashboard-connectivity.php'; ?>

	<div id="rtbcb-section-tests" style="display:none;">
		<h2 class="title"><?php esc_html_e( 'Individual Test Tools', 'rtbcb' ); ?></h2>
		<p><?php esc_html_e( 'These tools are optional and available after running all tests.', 'rtbcb' ); ?></p>

		<h2 class="nav-tab-wrapper" id="rtbcb-test-tabs">
			<a href="#rtbcb-phase1" class="nav-tab nav-tab-active"><?php echo esc_html( $phases[1]['label'] ); ?></a>
			<a href="#rtbcb-phase2" class="nav-tab"><?php echo esc_html( $phases[2]['label'] ); ?></a>
			<a href="#rtbcb-phase3" class="nav-tab"><?php echo esc_html( $phases[3]['label'] ); ?></a>
			<a href="#rtbcb-phase4" class="nav-tab"><?php echo esc_html( $phases[4]['label'] ); ?></a>
			<a href="#rtbcb-phase5" class="nav-tab"><?php echo esc_html( $phases[5]['label'] ); ?></a>
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
			<?php include RTBCB_DIR . 'admin/partials/test-roadmap-generator.php'; ?>
			<?php include RTBCB_DIR . 'admin/partials/test-roi-calculator.php'; ?>
			<?php include RTBCB_DIR . 'admin/partials/test-estimated-benefits.php'; ?>
		</div>
		<div id="rtbcb-phase4" class="rtbcb-tab-panel" style="display:none;">
			<?php include RTBCB_DIR . 'admin/partials/test-report-assembly.php'; ?>
		</div>
		<div id="rtbcb-phase5" class="rtbcb-tab-panel" style="display:none;">
			<?php include RTBCB_DIR . 'admin/partials/test-tracking-script.php'; ?>
			<?php include RTBCB_DIR . 'admin/partials/test-follow-up-email.php'; ?>
		</div>
	</div>

	<script>
	// Tabs handled in admin/js/rtbcb-admin.js
	</script>
</div>
