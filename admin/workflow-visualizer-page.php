<?php
defined( 'ABSPATH' ) || exit;

/**
 * Workflow Visualizer admin page.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

if ( ! current_user_can( 'manage_options' ) ) {
        return;
}

$rtbcb_wizard_questions = [
	[
		'title'  => __( 'Company', 'rtbcb' ),
		'fields' => [
			__( 'Company Name', 'rtbcb' ),
			__( 'Company Size (Annual Revenue)', 'rtbcb' ),
			__( 'Industry', 'rtbcb' ),
			__( 'Your Role (Optional)', 'rtbcb' ),
		],
	],
	[
		'title'  => __( 'Operations', 'rtbcb' ),
		'fields' => [
			__( 'Weekly Hours: Bank Reconciliation', 'rtbcb' ),
			__( 'Weekly Hours: Cash Positioning', 'rtbcb' ),
			__( 'Number of Banking Relationships', 'rtbcb' ),
			__( 'Treasury Team Size (FTEs)', 'rtbcb' ),
		],
	],
	[
		'title'  => __( 'Challenges', 'rtbcb' ),
		'fields' => [
			__( 'Manual Processes', 'rtbcb' ),
			__( 'Poor Visibility', 'rtbcb' ),
			__( 'Forecast Accuracy', 'rtbcb' ),
			__( 'Compliance Risk', 'rtbcb' ),
			__( 'Bank Fees', 'rtbcb' ),
			__( 'System Integration', 'rtbcb' ),
		],
	],
	[
		'title'  => __( 'Strategy', 'rtbcb' ),
		'fields' => [
			__( 'Current Treasury Technology', 'rtbcb' ),
			__( 'Primary Business Objective', 'rtbcb' ),
			__( 'Implementation Timeline', 'rtbcb' ),
			__( 'Decision Makers', 'rtbcb' ),
			__( 'Budget Range', 'rtbcb' ),
		],
	],
	[
		'title'  => __( 'Contact', 'rtbcb' ),
		'fields' => [
			__( 'Business Email Address', 'rtbcb' ),
			__( 'Consent', 'rtbcb' ),
			__( 'Report Type', 'rtbcb' ),
		],
	],
];
$rtbcb_prompt_structures = [
	[
		'title'  => __( 'Company Enrichment', 'rtbcb' ),
		'format' => "{\n\t\"company_profile\": { ... },\n\t\"industry_context\": { ... },\n\t\"strategic_insights\": [ ... ],\n\t\"enrichment_metadata\": { ... }\n}",
	],
	[
		'title'  => __( 'Benefits Estimate', 'rtbcb' ),
		'format' => "{\n\t\"time_savings_hours\": number,\n\t\"cost_reduction_usd\": number,\n\t\"efficiency_gain_percent\": number,\n\t\"roi_percent\": number,\n\t\"roi_timeline_months\": number,\n\t\"risk_mitigation\": string,\n\t\"productivity_gain_percent\": number\n}",
	],
	[
		'title'  => __( 'Implementation Roadmap', 'rtbcb' ),
		'format' => "{\n\t\"roadmap\": [ ... ],\n\t\"success_factors\": [ ... ]\n}",
	],
];

?>
<div class="wrap rtbcb-workflow-visualizer">
<h1><?php echo esc_html__( 'Treasury Report Workflow Visualizer', 'rtbcb' ); ?></h1>

<div class="rtbcb-workflow-controls">
<button type="button" id="rtbcb-refresh-workflow" class="button button-primary">
<?php echo esc_html__( 'Refresh Workflow History', 'rtbcb' ); ?>
</button>
<button type="button" id="rtbcb-clear-workflow" class="button">
<?php echo esc_html__( 'Clear History', 'rtbcb' ); ?>
</button>
</div>

<div id="rtbcb-workflow-message" class="notice" style="display:none;"></div>

<!-- Workflow Pipeline Visualization -->
<div class="rtbcb-workflow-pipeline">
<h2><?php echo esc_html__( 'Enhanced Workflow Pipeline', 'rtbcb' ); ?></h2>
<div class="rtbcb-pipeline-container">
<div class="rtbcb-pipeline-step" data-step="input_validation">
<div class="rtbcb-step-icon">📝</div>
<div class="rtbcb-step-title"><?php echo esc_html__( 'Input Validation', 'rtbcb' ); ?></div>
<div class="rtbcb-step-description"><?php echo esc_html__( 'Validate and sanitize user inputs', 'rtbcb' ); ?></div>
<div class="rtbcb-step-metrics">
<span class="rtbcb-step-duration">-</span>
<span class="rtbcb-step-status"><?php esc_html_e( 'pending', 'rtbcb' ); ?></span>
</div>
</div>

<div class="rtbcb-pipeline-arrow">→</div>

<div class="rtbcb-pipeline-step" data-step="ai_enrichment">
<div class="rtbcb-step-icon">🧠</div>
<div class="rtbcb-step-title"><?php echo esc_html__( 'AI Company Enrichment', 'rtbcb' ); ?></div>
<div class="rtbcb-step-description"><?php echo esc_html__( 'Single consolidated AI analysis', 'rtbcb' ); ?></div>
<div class="rtbcb-step-metrics">
<span class="rtbcb-step-duration">-</span>
<span class="rtbcb-step-status"><?php esc_html_e( 'pending', 'rtbcb' ); ?></span>
</div>
<div class="rtbcb-step-details">
<div class="rtbcb-detail-item">
<strong><?php echo esc_html__( 'AI Calls:', 'rtbcb' ); ?></strong> <span class="rtbcb-ai-calls">1</span>
</div>
</div>
</div>

<div class="rtbcb-pipeline-arrow">→</div>

<div class="rtbcb-pipeline-step" data-step="enhanced_roi_calculation">
<div class="rtbcb-step-icon">💰</div>
<div class="rtbcb-step-title"><?php echo esc_html__( 'Enhanced ROI Calculation', 'rtbcb' ); ?></div>
<div class="rtbcb-step-description"><?php echo esc_html__( 'AI-enhanced financial modeling', 'rtbcb' ); ?></div>
<div class="rtbcb-step-metrics">
<span class="rtbcb-step-duration">-</span>
<span class="rtbcb-step-status"><?php esc_html_e( 'pending', 'rtbcb' ); ?></span>
</div>
</div>

<div class="rtbcb-pipeline-arrow">→</div>

<div class="rtbcb-pipeline-step" data-step="intelligent_recommendations">
<div class="rtbcb-step-icon">🎯</div>
<div class="rtbcb-step-title"><?php echo esc_html__( 'Intelligent Recommendations', 'rtbcb' ); ?></div>
<div class="rtbcb-step-description"><?php echo esc_html__( 'AI-enhanced category selection', 'rtbcb' ); ?></div>
<div class="rtbcb-step-metrics">
<span class="rtbcb-step-duration">-</span>
<span class="rtbcb-step-status"><?php esc_html_e( 'pending', 'rtbcb' ); ?></span>
</div>
</div>

<div class="rtbcb-pipeline-arrow">→</div>

<div class="rtbcb-pipeline-step" data-step="hybrid_rag_analysis">
<div class="rtbcb-step-icon">🔍</div>
<div class="rtbcb-step-title"><?php echo esc_html__( 'Hybrid RAG Analysis', 'rtbcb' ); ?></div>
<div class="rtbcb-step-description"><?php echo esc_html__( 'RAG + AI strategic analysis', 'rtbcb' ); ?></div>
<div class="rtbcb-step-metrics">
<span class="rtbcb-step-duration">-</span>
<span class="rtbcb-step-status"><?php esc_html_e( 'pending', 'rtbcb' ); ?></span>
</div>
<div class="rtbcb-step-details">
<div class="rtbcb-detail-item">
<strong><?php echo esc_html__( 'AI Calls:', 'rtbcb' ); ?></strong> <span class="rtbcb-ai-calls">1</span>
</div>
</div>
</div>

<div class="rtbcb-pipeline-arrow">→</div>

<div class="rtbcb-pipeline-step" data-step="data_structuring">
<div class="rtbcb-step-icon">📊</div>
<div class="rtbcb-step-title"><?php echo esc_html__( 'Data Structuring', 'rtbcb' ); ?></div>
<div class="rtbcb-step-description"><?php echo esc_html__( 'Prepare structured report data', 'rtbcb' ); ?></div>
<div class="rtbcb-step-metrics">
<span class="rtbcb-step-duration">-</span>
<span class="rtbcb-step-status"><?php esc_html_e( 'pending', 'rtbcb' ); ?></span>
</div>
</div>
</div>

<div class="rtbcb-workflow-summary">
<div class="rtbcb-summary-item">
<strong><?php echo esc_html__( 'Total AI Calls:', 'rtbcb' ); ?></strong>
<span id="rtbcb-total-ai-calls">2</span>
<small><?php echo esc_html__( '(vs 5-8 in old workflow)', 'rtbcb' ); ?></small>
</div>
<div class="rtbcb-summary-item">
<strong><?php echo esc_html__( 'Expected Duration:', 'rtbcb' ); ?></strong>
<span>30-60s</span>
<small><?php echo esc_html__( '(vs 90-180s previously)', 'rtbcb' ); ?></small>
</div>
<div class="rtbcb-summary-item">
<strong><?php echo esc_html__( 'Data Flow:', 'rtbcb' ); ?></strong>
<span><?php echo esc_html__( 'AI First → Enhanced Logic → Template', 'rtbcb' ); ?></span>
</div>
</div>
</div>

<!-- Recent Workflow Executions -->
<div class="rtbcb-workflow-history">
<h2><?php echo esc_html__( 'Recent Workflow Executions', 'rtbcb' ); ?></h2>
<div id="rtbcb-workflow-history-container">
<div class="rtbcb-loading"><?php echo esc_html__( 'Loading workflow history...', 'rtbcb' ); ?></div>
</div>
</div>
<!-- Wizard Questions and Prompts -->
<div class="rtbcb-wizard-prompts">
	<h2><?php echo esc_html__( 'Wizard Questions & Prompt Structure', 'rtbcb' ); ?></h2>
	<div class="rtbcb-wizard-prompts-columns">
		<div class="rtbcb-wizard-section">
			<h3><?php echo esc_html__( 'Wizard Questions', 'rtbcb' ); ?></h3>
			<ol>
				<?php foreach ( $rtbcb_wizard_questions as $section ) : ?>
				<li>
				<strong><?php echo esc_html( $section['title'] ); ?></strong>
				<ul>
					<?php foreach ( $section['fields'] as $field ) : ?>
					<li><?php echo esc_html( $field ); ?></li>
					<?php endforeach; ?>
				</ul>
			</li>
			<?php endforeach; ?>
		</ol>
	</div>
	<div class="rtbcb-wizard-section">
		<h3><?php echo esc_html__( 'Prompt Structures', 'rtbcb' ); ?></h3>
		<?php foreach ( $rtbcb_prompt_structures as $prompt ) : ?>
		<div class="rtbcb-prompt-structure">
			<h4><?php echo esc_html( $prompt['title'] ); ?></h4>
			<pre><?php echo esc_html( $prompt['format'] ); ?></pre>
		</div>
		<?php endforeach; ?>
	</div>
</div>
</div>
<!-- Debug Interface -->
<div class="rtbcb-debug-interface" style="display: none;">
<h2><?php echo esc_html__( 'Debug Information', 'rtbcb' ); ?></h2>
<div class="rtbcb-debug-tabs">
<button type="button" class="rtbcb-tab-button active" data-tab="prompts">
<?php echo esc_html__( 'AI Prompts', 'rtbcb' ); ?>
</button>
<button type="button" class="rtbcb-tab-button" data-tab="responses">
<?php echo esc_html__( 'AI Responses', 'rtbcb' ); ?>
</button>
<button type="button" class="rtbcb-tab-button" data-tab="performance">
<?php echo esc_html__( 'Performance', 'rtbcb' ); ?>
</button>
<button type="button" class="rtbcb-tab-button" data-tab="errors">
<?php echo esc_html__( 'Errors & Warnings', 'rtbcb' ); ?>
</button>
</div>
<div id="rtbcb-debug-content"></div>
</div>
</div>
