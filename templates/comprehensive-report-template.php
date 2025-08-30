<?php
/**
* Enhanced Comprehensive Report Template
* 
* This template now handles structured data from the refactored workflow
* and generates a modern dashboard-style interface with:
* - Interactive charts and metrics
* - Collapsible sections
* - Enhanced visual design
* - Mobile-responsive layout
* 
* @package RealTreasuryBusinessCaseBuilder
* @var array $report_data Structured report data from the new workflow
*/

defined( 'ABSPATH' ) || exit;

// Extract structured data sections
$metadata             = $report_data['metadata'] ?? [];
$executive_summary    = $report_data['executive_summary'] ?? [];
$company_intelligence = $report_data['company_intelligence'] ?? [];
$financial_analysis   = $report_data['financial_analysis'] ?? [];
$technology_strategy  = $report_data['technology_strategy'] ?? [];
$operational_insights = $report_data['operational_insights'] ?? [];
$risk_analysis        = $report_data['risk_analysis'] ?? [];
$action_plan          = $report_data['action_plan'] ?? [];
$rag_context          = $report_data['rag_context'] ?? [];

$company_name    = $metadata['company_name'] ?? __( 'Your Company', 'rtbcb' );
$analysis_date   = $metadata['analysis_date'] ?? current_time( 'Y-m-d' );
$confidence_level = round( ( $metadata['confidence_level'] ?? 0.85 ) * 100 );
$processing_time = $metadata['processing_time'] ?? 0;
?>

<div class="rtbcb-enhanced-report" data-company="<?php echo esc_attr( $company_name ); ?>">
	
	<!-- Enhanced Report Header with Metrics Dashboard -->
	<div class="rtbcb-report-header-enhanced">
		<div class="rtbcb-header-content">
			<div class="rtbcb-header-main">
				<div class="rtbcb-report-badge-enhanced">
					<span class="rtbcb-badge-icon">🏆</span>
					<span class="rtbcb-badge-text"><?php echo esc_html__( 'AI-ENHANCED ANALYSIS', 'rtbcb' ); ?></span>
					<div class="rtbcb-confidence-meter">
						<div class="rtbcb-confidence-bar" style="width: <?php echo esc_attr( $confidence_level ); ?>%"></div>
						<span class="rtbcb-confidence-text"><?php echo esc_html( $confidence_level ); ?>% <?php echo esc_html__( 'Confidence', 'rtbcb' ); ?></span>
					</div>
				</div>
				
				<h1 class="rtbcb-report-title-enhanced">
					<?php echo esc_html( $company_name ); ?> 
					<span class="rtbcb-title-subtitle"><?php echo esc_html__( 'Treasury Technology Business Case', 'rtbcb' ); ?></span>
				</h1>
				
				<div class="rtbcb-report-meta-enhanced">
					<div class="rtbcb-meta-item">
						<span class="rtbcb-meta-icon">📅</span>
						<span class="rtbcb-meta-label"><?php echo esc_html__( 'Analysis Date', 'rtbcb' ); ?></span>
						<span class="rtbcb-meta-value"><?php echo esc_html( $analysis_date ); ?></span>
					</div>
					<div class="rtbcb-meta-item">
						<span class="rtbcb-meta-icon">⚡</span>
						<span class="rtbcb-meta-label"><?php echo esc_html__( 'Processing Time', 'rtbcb' ); ?></span>
						<span class="rtbcb-meta-value"><?php echo esc_html( round( $processing_time, 1 ) ); ?>s</span>
					</div>
					<div class="rtbcb-meta-item">
						<span class="rtbcb-meta-icon">📊</span>
						<span class="rtbcb-meta-label"><?php echo esc_html__( 'Analysis Type', 'rtbcb' ); ?></span>
						<span class="rtbcb-meta-value"><?php echo esc_html__( 'Comprehensive Enhanced', 'rtbcb' ); ?></span>
					</div>
				</div>
			</div>

			<!-- Key Metrics Dashboard -->
			<div class="rtbcb-metrics-dashboard">
				<?php if ( ! empty( $financial_analysis['roi_scenarios'] ) ) : ?>
					<?php $base_roi = $financial_analysis['roi_scenarios']['base'] ?? []; ?>
					<div class="rtbcb-metric-card primary">
						<div class="rtbcb-metric-icon">💰</div>
						<div class="rtbcb-metric-content">
							<div class="rtbcb-metric-value">
								$<?php echo esc_html( number_format( $base_roi['total_annual_benefit'] ?? 0 ) ); ?>
							</div>
							<div class="rtbcb-metric-label"><?php echo esc_html__( 'Annual ROI (Base Case)', 'rtbcb' ); ?></div>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $financial_analysis['payback_analysis'] ) ) : ?>
					<div class="rtbcb-metric-card">
						<div class="rtbcb-metric-icon">⏱️</div>
						<div class="rtbcb-metric-content">
							<div class="rtbcb-metric-value">
								<?php echo esc_html( $financial_analysis['payback_analysis']['payback_months'] ?? 'N/A' ); ?>
							</div>
							<div class="rtbcb-metric-label"><?php echo esc_html__( 'Months to Payback', 'rtbcb' ); ?></div>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $technology_strategy['recommended_category'] ) ) : ?>
					<div class="rtbcb-metric-card">
						<div class="rtbcb-metric-icon">🎯</div>
						<div class="rtbcb-metric-content">
							<div class="rtbcb-metric-value recommended-category">
								<?php echo esc_html( ucwords( str_replace( '_', ' ', $technology_strategy['recommended_category'] ) ) ); ?>
							</div>
							<div class="rtbcb-metric-label"><?php echo esc_html__( 'Recommended Solution', 'rtbcb' ); ?></div>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $company_intelligence['enriched_profile']['maturity_level'] ) ) : ?>
					<div class="rtbcb-metric-card">
						<div class="rtbcb-metric-icon">📈</div>
						<div class="rtbcb-metric-content">
							<div class="rtbcb-metric-value maturity-level">
								<?php echo esc_html( ucfirst( $company_intelligence['enriched_profile']['maturity_level'] ) ); ?>
							</div>
							<div class="rtbcb-metric-label"><?php echo esc_html__( 'Treasury Maturity', 'rtbcb' ); ?></div>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Executive Summary with Enhanced Visual Design -->
	<?php if ( ! empty( $executive_summary ) ) : ?>
	<div class="rtbcb-section-enhanced rtbcb-executive-summary-enhanced">
		<div class="rtbcb-section-header-enhanced">
			<h2 class="rtbcb-section-title">
				<span class="rtbcb-section-icon">📋</span>
				<?php echo esc_html__( 'Executive Summary', 'rtbcb' ); ?>
			</h2>
			<div class="rtbcb-business-case-strength-enhanced <?php echo esc_attr( strtolower( $executive_summary['business_case_strength'] ?? 'strong' ) ); ?>">
				<span class="rtbcb-strength-indicator"></span>
				<?php echo esc_html( $executive_summary['business_case_strength'] ?? esc_html__( 'Strong', 'rtbcb' ) ); ?> 
				<?php echo esc_html__( 'Business Case', 'rtbcb' ); ?>
			</div>
		</div>
		
		<div class="rtbcb-section-content">
			<?php if ( ! empty( $executive_summary['strategic_positioning'] ) ) : ?>
				<div class="rtbcb-strategic-positioning-enhanced">
					<div class="rtbcb-content-card">
						<h3><?php echo esc_html__( 'Strategic Positioning', 'rtbcb' ); ?></h3>
						<p class="rtbcb-strategic-text"><?php echo esc_html( $executive_summary['strategic_positioning'] ); ?></p>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $executive_summary['key_value_drivers'] ) ) : ?>
				<div class="rtbcb-value-drivers-enhanced">
					<h3><?php echo esc_html__( 'Key Value Drivers', 'rtbcb' ); ?></h3>
					<div class="rtbcb-value-drivers-grid-enhanced">
						<?php foreach ( $executive_summary['key_value_drivers'] as $index => $driver ) : ?>
							<div class="rtbcb-value-driver-enhanced">
								<div class="rtbcb-driver-number-enhanced"><?php echo esc_html( $index + 1 ); ?></div>
								<div class="rtbcb-driver-content">
									<div class="rtbcb-driver-text"><?php echo esc_html( $driver ); ?></div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $executive_summary['executive_recommendation'] ) ) : ?>
				<div class="rtbcb-executive-recommendation-enhanced">
					<div class="rtbcb-recommendation-card">
						<div class="rtbcb-recommendation-header">
							<span class="rtbcb-recommendation-icon">💡</span>
							<h3><?php echo esc_html__( 'Executive Recommendation', 'rtbcb' ); ?></h3>
						</div>
						<div class="rtbcb-recommendation-content">
							<?php echo esc_html( $executive_summary['executive_recommendation'] ); ?>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>

	<!-- Interactive ROI Analysis Section with Charts -->
	<?php if ( ! empty( $financial_analysis['roi_scenarios'] ) ) : ?>
	<div class="rtbcb-section-enhanced rtbcb-financial-analysis-enhanced">
		<div class="rtbcb-section-header-enhanced">
			<h2 class="rtbcb-section-title">
				<span class="rtbcb-section-icon">💰</span>
				<?php echo esc_html__( 'Financial Analysis & ROI Projections', 'rtbcb' ); ?>
			</h2>
			<button type="button" class="rtbcb-section-toggle" data-target="financial-content">
				<span class="rtbcb-toggle-text"><?php echo esc_html__( 'Expand', 'rtbcb' ); ?></span>
				<span class="rtbcb-toggle-arrow">▼</span>
			</button>
		</div>
		
		<div id="financial-content" class="rtbcb-section-content">
			<!-- ROI Scenarios Chart -->
			<div class="rtbcb-roi-chart-container">
				<h3><?php echo esc_html__( 'ROI Scenario Analysis', 'rtbcb' ); ?></h3>
				<canvas id="rtbcb-roi-chart" width="800" height="400"></canvas>
				<div class="rtbcb-chart-legend">
					<div class="rtbcb-legend-item">
						<span class="rtbcb-legend-color conservative"></span>
						<span><?php echo esc_html__( 'Conservative Scenario', 'rtbcb' ); ?></span>
					</div>
					<div class="rtbcb-legend-item">
						<span class="rtbcb-legend-color base"></span>
						<span><?php echo esc_html__( 'Base Case', 'rtbcb' ); ?></span>
					</div>
					<div class="rtbcb-legend-item">
						<span class="rtbcb-legend-color optimistic"></span>
						<span><?php echo esc_html__( 'Optimistic Scenario', 'rtbcb' ); ?></span>
					</div>
				</div>
			</div>

			<!-- ROI Breakdown -->
			<div class="rtbcb-roi-breakdown-enhanced">
				<h3><?php echo esc_html__( 'ROI Component Breakdown', 'rtbcb' ); ?></h3>
				<div class="rtbcb-roi-components">
					<?php foreach ( $financial_analysis['roi_scenarios'] as $scenario_name => $scenario ) : ?>
						<div class="rtbcb-scenario-card <?php echo esc_attr( $scenario_name ); ?>">
							<h4><?php echo esc_html( ucfirst( $scenario_name ) ); ?> <?php echo esc_html__( 'Case', 'rtbcb' ); ?></h4>
							<div class="rtbcb-scenario-metrics">
								<div class="rtbcb-scenario-metric">
									<span class="rtbcb-metric-label"><?php echo esc_html__( 'Total Annual Benefit', 'rtbcb' ); ?></span>
									<span class="rtbcb-metric-value primary">$<?php echo esc_html( number_format( $scenario['total_annual_benefit'] ?? 0 ) ); ?></span>
								</div>
								<div class="rtbcb-scenario-metric">
									<span class="rtbcb-metric-label"><?php echo esc_html__( 'Labor Savings', 'rtbcb' ); ?></span>
									<span class="rtbcb-metric-value">$<?php echo esc_html( number_format( $scenario['labor_savings'] ?? 0 ) ); ?></span>
								</div>
								<div class="rtbcb-scenario-metric">
									<span class="rtbcb-metric-label"><?php echo esc_html__( 'Fee Savings', 'rtbcb' ); ?></span>
									<span class="rtbcb-metric-value">$<?php echo esc_html( number_format( $scenario['fee_savings'] ?? 0 ) ); ?></span>
								</div>
								<div class="rtbcb-scenario-metric">
									<span class="rtbcb-metric-label"><?php echo esc_html__( 'Error Reduction', 'rtbcb' ); ?></span>
									<span class="rtbcb-metric-value">$<?php echo esc_html( number_format( $scenario['error_reduction'] ?? 0 ) ); ?></span>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Sensitivity Analysis -->
			<?php if ( ! empty( $financial_analysis['sensitivity_analysis'] ) ) : ?>
				<div class="rtbcb-sensitivity-analysis">
					<h3><?php echo esc_html__( 'Sensitivity Analysis', 'rtbcb' ); ?></h3>
					<div class="rtbcb-sensitivity-grid">
						<?php foreach ( $financial_analysis['sensitivity_analysis'] as $factor ) : ?>
							<div class="rtbcb-sensitivity-item">
								<div class="rtbcb-sensitivity-header">
									<span class="rtbcb-sensitivity-factor"><?php echo esc_html( $factor['factor'] ?? '' ); ?></span>
									<span class="rtbcb-sensitivity-probability"><?php echo esc_html( round( ( $factor['probability'] ?? 0 ) * 100 ) ); ?>% <?php echo esc_html__( 'likelihood', 'rtbcb' ); ?></span>
								</div>
								<div class="rtbcb-sensitivity-impact <?php
									// Escaped for safe output.
									echo esc_attr( ( $factor['impact_percentage'] ?? 0 ) >= 0 ? 'positive' : 'negative' );
								?>">
									<?php echo esc_html( $factor['impact_percentage'] ?? 0 ); ?>% <?php echo esc_html__( 'impact', 'rtbcb' ); ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>

	<!-- Company Intelligence Section (AI-Enhanced) -->
	<?php if ( ! empty( $company_intelligence ) ) : ?>
	<div class="rtbcb-section-enhanced rtbcb-company-intelligence">
		<div class="rtbcb-section-header-enhanced">
			<h2 class="rtbcb-section-title">
				<span class="rtbcb-section-icon">🧠</span>
				<?php echo esc_html__( 'AI-Enhanced Company Intelligence', 'rtbcb' ); ?>
			</h2>
			<div class="rtbcb-ai-badge">
				<span class="rtbcb-ai-icon">✨</span>
				<?php echo esc_html__( 'AI Enriched', 'rtbcb' ); ?>
			</div>
		</div>
		
		<div class="rtbcb-section-content">
			<div class="rtbcb-intelligence-grid">
				<?php if ( ! empty( $company_intelligence['enriched_profile'] ) ) : ?>
					<div class="rtbcb-intelligence-card">
						<h3><?php echo esc_html__( 'Company Profile', 'rtbcb' ); ?></h3>
						<div class="rtbcb-profile-details">
							<?php if ( ! empty( $company_intelligence['enriched_profile']['enhanced_description'] ) ) : ?>
								<p><?php echo esc_html( $company_intelligence['enriched_profile']['enhanced_description'] ); ?></p>
							<?php endif; ?>
							
							<?php if ( ! empty( $company_intelligence['enriched_profile']['treasury_maturity'] ) ) : ?>
								<div class="rtbcb-maturity-assessment">
									<h4><?php echo esc_html__( 'Treasury Maturity Assessment', 'rtbcb' ); ?></h4>
									<div class="rtbcb-maturity-level <?php echo esc_attr( $company_intelligence['enriched_profile']['maturity_level'] ?? 'basic' ); ?>">
										<?php echo esc_html( ucfirst( $company_intelligence['enriched_profile']['maturity_level'] ?? 'basic' ) ); ?>
									</div>
									<p><?php echo esc_html( $company_intelligence['enriched_profile']['treasury_maturity']['current_state'] ?? '' ); ?></p>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $company_intelligence['industry_context'] ) ) : ?>
					<div class="rtbcb-intelligence-card">
						<h3><?php echo esc_html__( 'Industry Context', 'rtbcb' ); ?></h3>
						<div class="rtbcb-industry-insights">
							<?php if ( ! empty( $company_intelligence['industry_context']['sector_analysis']['market_dynamics'] ) ) : ?>
								<div class="rtbcb-insight-item">
									<strong><?php echo esc_html__( 'Market Dynamics:', 'rtbcb' ); ?></strong>
									<span><?php echo esc_html( $company_intelligence['industry_context']['sector_analysis']['market_dynamics'] ); ?></span>
								</div>
							<?php endif; ?>
							
							<?php if ( ! empty( $company_intelligence['industry_context']['benchmarking']['technology_penetration'] ) ) : ?>
								<div class="rtbcb-insight-item">
									<strong><?php echo esc_html__( 'Technology Adoption:', 'rtbcb' ); ?></strong>
									<span class="rtbcb-adoption-level <?php echo esc_attr( $company_intelligence['industry_context']['benchmarking']['technology_penetration'] ); ?>">
										<?php echo esc_html( ucfirst( $company_intelligence['industry_context']['benchmarking']['technology_penetration'] ) ); ?>
									</span>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<!-- Action Plan Section with Timeline -->
	<?php if ( ! empty( $action_plan ) ) : ?>
	<div class="rtbcb-section-enhanced rtbcb-action-plan">
		<div class="rtbcb-section-header-enhanced">
			<h2 class="rtbcb-section-title">
				<span class="rtbcb-section-icon">🚀</span>
				<?php echo esc_html__( 'Implementation Action Plan', 'rtbcb' ); ?>
			</h2>
		</div>
		
		<div class="rtbcb-section-content">
			<div class="rtbcb-timeline-container">
				<?php if ( ! empty( $action_plan['immediate_steps'] ) ) : ?>
					<div class="rtbcb-timeline-phase immediate">
						<div class="rtbcb-timeline-header">
							<div class="rtbcb-timeline-icon">⚡</div>
							<h3><?php echo esc_html__( 'Immediate Actions', 'rtbcb' ); ?></h3>
							<span class="rtbcb-timeline-duration"><?php echo esc_html__( 'Next 30 days', 'rtbcb' ); ?></span>
						</div>
						<ul class="rtbcb-action-list">
							<?php foreach ( $action_plan['immediate_steps'] as $step ) : ?>
								<li><?php echo esc_html( $step ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $action_plan['short_term_milestones'] ) ) : ?>
					<div class="rtbcb-timeline-phase short-term">
						<div class="rtbcb-timeline-header">
							<div class="rtbcb-timeline-icon">📅</div>
							<h3><?php echo esc_html__( 'Short-term Milestones', 'rtbcb' ); ?></h3>
							<span class="rtbcb-timeline-duration"><?php echo esc_html__( '3-6 months', 'rtbcb' ); ?></span>
						</div>
						<ul class="rtbcb-action-list">
							<?php foreach ( $action_plan['short_term_milestones'] as $milestone ) : ?>
								<li><?php echo esc_html( $milestone ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $action_plan['long_term_objectives'] ) ) : ?>
					<div class="rtbcb-timeline-phase long-term">
						<div class="rtbcb-timeline-header">
							<div class="rtbcb-timeline-icon">🎯</div>
							<h3><?php echo esc_html__( 'Long-term Objectives', 'rtbcb' ); ?></h3>
							<span class="rtbcb-timeline-duration"><?php echo esc_html__( '6+ months', 'rtbcb' ); ?></span>
						</div>
						<ul class="rtbcb-action-list">
							<?php foreach ( $action_plan['long_term_objectives'] as $objective ) : ?>
								<li><?php echo esc_html( $objective ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php endif; ?>

	<!-- Supporting Context Section -->
	<?php if ( ! empty( $rag_context ) ) : ?>
	<div class="rtbcb-section-enhanced rtbcb-supporting-context">
		<div class="rtbcb-section-header-enhanced">
			<h2 class="rtbcb-section-title">
				<span class="rtbcb-section-icon">📚</span>
				<?php echo esc_html__( 'Supporting Context', 'rtbcb' ); ?>
			</h2>
		</div>
		<div class="rtbcb-section-content">
			<ul class="rtbcb-context-list">
				<?php foreach ( (array) $rag_context as $context_item ) : ?>
					<li><?php echo esc_html( $context_item ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<?php endif; ?>

	<!-- Enhanced Report Footer -->
	<div class="rtbcb-report-footer-enhanced">
		<div class="rtbcb-footer-content">
			<div class="rtbcb-footer-branding">
				<div class="rtbcb-footer-logo">
					<span class="rtbcb-logo-icon">💎</span>
					<span class="rtbcb-logo-text"><?php echo esc_html__( 'Real Treasury', 'rtbcb' ); ?></span>
				</div>
				<div class="rtbcb-footer-tagline">
					<?php echo esc_html__( 'AI-Enhanced Treasury Technology Analysis', 'rtbcb' ); ?>
				</div>
			</div>
			
			<div class="rtbcb-footer-actions">
				<button type="button" class="rtbcb-action-button primary" onclick="window.print()">
					<span class="rtbcb-button-icon">🖨️</span>
					<?php echo esc_html__( 'Print Report', 'rtbcb' ); ?>
				</button>
				<button type="button" class="rtbcb-action-button secondary" onclick="rtbcbExportPDF()">
					<span class="rtbcb-button-icon">📄</span>
					<?php echo esc_html__( 'Export PDF', 'rtbcb' ); ?>
				</button>
			</div>
		</div>
		
		<div class="rtbcb-footer-meta">
			<div class="rtbcb-disclaimer-enhanced">
				<p><strong><?php echo esc_html__( 'Analysis Disclaimer:', 'rtbcb' ); ?></strong> 
				<?php echo esc_html__( 'This AI-enhanced analysis is based on provided information and industry benchmarks. Results may vary depending on implementation approach and organizational factors. Confidence level reflects data quality and analysis depth.', 'rtbcb' ); ?></p>
			</div>
			<div class="rtbcb-footer-stats">
				<span><?php printf( esc_html__( 'Confidence: %s%%', 'rtbcb' ), esc_html( $confidence_level ) ); ?></span>
				<span><?php printf( esc_html__( 'Generated: %s', 'rtbcb' ), esc_html( $analysis_date ) ); ?></span>
				<span><?php printf( esc_html__( 'Processing: %ss', 'rtbcb' ), esc_html( round( $processing_time, 1 ) ) ); ?></span>
			</div>
		</div>
	</div>
</div>

<!-- Enhanced JavaScript for Interactivity -->
<script>
document.addEventListener('DOMContentLoaded', function() {
	// Initialize ROI Chart if Chart.js is available
	if (typeof Chart !== 'undefined') {
		initializeROIChart();
	}
	
	// Initialize collapsible sections
	initializeSectionToggles();
	
	// Initialize interactive elements
	initializeInteractiveFeatures();
});

function initializeROIChart() {
	const ctx = document.getElementById('rtbcb-roi-chart');
	if (!ctx) return;
	
	const roiData = <?php echo wp_json_encode( $financial_analysis['roi_scenarios'] ?? [] ); ?>;
	
	new Chart(ctx, {
		type: 'bar',
		data: {
			labels: ['<?php echo esc_js( __( 'Labor Savings', 'rtbcb' ) ); ?>', '<?php echo esc_js( __( 'Fee Savings', 'rtbcb' ) ); ?>', '<?php echo esc_js( __( 'Error Reduction', 'rtbcb' ) ); ?>', '<?php echo esc_js( __( 'Total Benefit', 'rtbcb' ) ); ?>'],
			datasets: [
				{
					label: '<?php echo esc_js( __( 'Conservative', 'rtbcb' ) ); ?>',
					data: [
						roiData.conservative?.labor_savings || 0,
						roiData.conservative?.fee_savings || 0, 
						roiData.conservative?.error_reduction || 0,
						roiData.conservative?.total_annual_benefit || 0
					],
					backgroundColor: 'rgba(239, 68, 68, 0.8)',
					borderColor: 'rgba(239, 68, 68, 1)',
					borderWidth: 1
				},
				{
					label: '<?php echo esc_js( __( 'Base Case', 'rtbcb' ) ); ?>',
					data: [
						roiData.base?.labor_savings || 0,
						roiData.base?.fee_savings || 0,
						roiData.base?.error_reduction || 0,
						roiData.base?.total_annual_benefit || 0
					],
					backgroundColor: 'rgba(59, 130, 246, 0.8)',
					borderColor: 'rgba(59, 130, 246, 1)',
					borderWidth: 1
				},
				{
					label: '<?php echo esc_js( __( 'Optimistic', 'rtbcb' ) ); ?>',
					data: [
						roiData.optimistic?.labor_savings || 0,
						roiData.optimistic?.fee_savings || 0,
						roiData.optimistic?.error_reduction || 0,
						roiData.optimistic?.total_annual_benefit || 0
					],
					backgroundColor: 'rgba(16, 185, 129, 0.8)',
					borderColor: 'rgba(16, 185, 129, 1)',
					borderWidth: 1
				}
			]
		},
		options: {
			responsive: true,
			scales: {
				y: {
					beginAtZero: true,
					ticks: {
						callback: function(value) {
							return '$' + new Intl.NumberFormat().format(value);
						}
					}
				}
			},
			plugins: {
				tooltip: {
					callbacks: {
						label: function(context) {
							return context.dataset.label + ': $' + new Intl.NumberFormat().format(context.raw);
						}
					}
				}
			}
		}
	});
}

function initializeSectionToggles() {
	document.querySelectorAll('.rtbcb-section-toggle').forEach(toggle => {
		toggle.addEventListener('click', function() {
			const targetId = this.getAttribute('data-target');
			const content = document.getElementById(targetId);
			const arrow = this.querySelector('.rtbcb-toggle-arrow');
			const text = this.querySelector('.rtbcb-toggle-text');
			
			if (content) {
				content.style.display = content.style.display === 'none' ? 'block' : 'none';
				arrow.textContent = content.style.display === 'none' ? '▼' : '▲';
				text.textContent = content.style.display === 'none' ? '<?php echo esc_js( __( 'Expand', 'rtbcb' ) ); ?>' : '<?php echo esc_js( __( 'Collapse', 'rtbcb' ) ); ?>';
			}
		});
	});
}

function initializeInteractiveFeatures() {
	// Add smooth scrolling to sections
	document.querySelectorAll('.rtbcb-section-enhanced').forEach((section, index) => {
		section.style.animationDelay = (index * 0.1) + 's';
		section.classList.add('rtbcb-fade-in');
	});
	
	// Add click handlers for metric cards
	document.querySelectorAll('.rtbcb-metric-card').forEach(card => {
		card.addEventListener('click', function() {
			this.classList.toggle('expanded');
		});
	});
}

function rtbcbExportPDF() {
	// PDF export functionality
	window.print();
}
</script>

<?php
// Pass structured data to JavaScript for charts and interactivity
wp_localize_script( 'rtbcb-report', 'rtbcbReportData', [
	'roiScenarios' => $financial_analysis['roi_scenarios'] ?? [],
	'companyName' => $company_name,
	'confidence' => $confidence_level,
	'strings' => [
		'exportPDF' => __( 'Export as PDF', 'rtbcb' ),
		'printReport' => __( 'Print Report', 'rtbcb' ),
		'expandSection' => __( 'Expand Section', 'rtbcb' ),
		'collapseSection' => __( 'Collapse Section', 'rtbcb' )
	]
] );
?>
