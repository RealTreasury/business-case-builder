<?php
defined( 'ABSPATH' ) || exit;

/**
 * Enhanced Calculator that uses AI-enriched company data for improved accuracy.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */
class RTBCB_Enhanced_Calculator extends RTBCB_Calculator {
/**
 * Calculate enhanced ROI using AI-enriched company intelligence.
 *
 * @param array $user_inputs      Original user inputs.
 * @param array $enriched_profile AI-enriched company profile.
 * @return array Enhanced ROI scenarios with sensitivity analysis.
 */
public function calculate_enhanced_roi( $user_inputs, $enriched_profile ) {
$base_scenarios = parent::calculate_roi( $user_inputs );

// Apply AI insights to enhance calculations.
$enhancement_factors = $this->calculate_enhancement_factors( $enriched_profile );
$enhanced_scenarios  = $this->apply_enhancement_factors( $base_scenarios, $enhancement_factors );

// Add sensitivity analysis.
$enhanced_scenarios['sensitivity_analysis'] = $this->perform_sensitivity_analysis(
$enhanced_scenarios,
$enriched_profile
);

// Add confidence scoring.
$enhanced_scenarios['confidence_metrics'] = $this->calculate_confidence_metrics(
$enriched_profile,
$user_inputs
);

return $enhanced_scenarios;
}

/**
 * Calculate enhancement factors based on AI insights.
 *
 * @param array $enriched_profile Enriched profile data.
 * @return array Enhancement factor data.
 */
private function calculate_enhancement_factors( $enriched_profile ) {
$company_profile   = $enriched_profile['company_profile'] ?? [];
$industry_context  = $enriched_profile['industry_context'] ?? [];
$strategic_insight = $enriched_profile['strategic_insights'] ?? [];

// Maturity level adjustment.
$maturity_multiplier = $this->get_maturity_multiplier(
$company_profile['maturity_level'] ?? 'basic'
);

// Industry-specific factors.
$industry_multiplier = $this->get_industry_multiplier( $industry_context );

// Readiness assessment impact.
$readiness_multiplier = $this->get_readiness_multiplier(
$company_profile['treasury_maturity']['automation_readiness'] ?? 'medium'
);

// Financial health impact.
$financial_multiplier = $this->get_financial_health_multiplier(
$company_profile['financial_indicators']['financial_health'] ?? 'stable'
);

return [
'efficiency_factor'    => $maturity_multiplier * $readiness_multiplier,
'risk_factor'          => $financial_multiplier * $industry_multiplier,
'implementation_factor'=> $this->calculate_implementation_complexity_factor( $strategic_insight ),
'timeline_factor'      => $this->calculate_timeline_factor( $enriched_profile ),
'confidence_factor'    => floatval( $enriched_profile['enrichment_metadata']['confidence_level'] ?? 0.8 ),
];
}

/**
 * Apply enhancement factors to base scenarios.
 *
 * @param array $scenarios Base ROI scenarios.
 * @param array $factors   Enhancement factors.
 * @return array Enhanced scenarios.
 */
private function apply_enhancement_factors( $scenarios, $factors ) {
foreach ( $scenarios as $key => &$scenario ) {
if ( isset( $scenario['total_annual_benefit'] ) ) {
$scenario['total_annual_benefit'] *= $factors['efficiency_factor'] * $factors['risk_factor'];
$scenario['total_annual_benefit'] *= $factors['implementation_factor'] * $factors['timeline_factor'];
$scenario['confidence_adjustment'] = $factors['confidence_factor'];
}
}

return $scenarios;
}

/**
\t * Get maturity level multiplier for ROI calculations.
 *
 * @param string $maturity_level Maturity level key.
 * @return float Multiplier.
 */
private function get_maturity_multiplier( $maturity_level ) {
$multipliers = [
'basic'      => 1.2,
'developing' => 1.1,
'strategic'  => 0.9,
'optimized'  => 0.7,
];

return $multipliers[ $maturity_level ] ?? 1.0;
}

/**
 * Get industry-specific multiplier.
 *
 * @param array $industry_context Industry context data.
 * @return float Multiplier.
 */
private function get_industry_multiplier( $industry_context ) {
$sector_analysis    = $industry_context['sector_analysis'] ?? [];
$technology_adopt   = $sector_analysis['technology_adoption'] ?? 'mainstream';
$adoption_multipliers = [
'laggard'   => 1.3,
'follower'  => 1.1,
'mainstream'=> 1.0,
'leader'    => 0.8,
];

return $adoption_multipliers[ $technology_adopt ] ?? 1.0;
}

/**
 * Get readiness multiplier based on automation readiness.
 *
 * @param string $readiness Automation readiness level.
 * @return float Multiplier.
 */
private function get_readiness_multiplier( $readiness ) {
$map = [
'low'    => 0.8,
'medium' => 1.0,
'high'   => 1.2,
];

return $map[ $readiness ] ?? 1.0;
}

/**
 * Get financial health multiplier.
 *
 * @param string $health Financial health indicator.
 * @return float Multiplier.
 */
private function get_financial_health_multiplier( $health ) {
$map = [
'weak'   => 0.9,
'stable' => 1.0,
'strong' => 1.1,
];

return $map[ $health ] ?? 1.0;
}

/**
 * Calculate implementation complexity factor.
 *
 * @param array $strategic_insight Strategic insight data.
 * @return float Multiplier.
 */
private function calculate_implementation_complexity_factor( $strategic_insight ) {
$complexity = $strategic_insight['implementation_complexity'] ?? 'medium';
$map        = [
'low'      => 1.1,
'medium'   => 1.0,
'high'     => 0.9,
'very_high'=> 0.8,
];

return $map[ $complexity ] ?? 1.0;
}

/**
 * Calculate timeline factor.
 *
 * @param array $enriched_profile Enriched profile data.
 * @return float Multiplier.
 */
private function calculate_timeline_factor( $enriched_profile ) {
$timeline = $enriched_profile['strategic_insights']['implementation_timeline'] ?? 'standard';
$map      = [
'accelerated' => 1.1,
'standard'   => 1.0,
'extended'   => 0.9,
];

return $map[ $timeline ] ?? 1.0;
}

/**
 * Perform sensitivity analysis on ROI calculations.
 *
 * @param array $scenarios        Enhanced scenarios.
 * @param array $enriched_profile Enriched profile data.
 * @return array Sensitivity analysis results.
 */
private function perform_sensitivity_analysis( $scenarios, $enriched_profile ) {
$base_case    = $scenarios['base'];
$base_benefit = $base_case['total_annual_benefit'];

return [
'implementation_delay' => [
'factor'            => 'Implementation delay (3 months)',
'impact_percentage' => -15,
'adjusted_benefit'  => $base_benefit * 0.85,
'probability'       => $this->calculate_delay_probability( $enriched_profile ),
],
'adoption_resistance' => [
'factor'            => 'User adoption challenges',
'impact_percentage' => -25,
'adjusted_benefit'  => $base_benefit * 0.75,
'probability'       => $this->calculate_resistance_probability( $enriched_profile ),
],
'technology_evolution'=> [
'factor'            => 'Faster technology evolution',
'impact_percentage' => 10,
'adjusted_benefit'  => $base_benefit * 1.10,
'probability'       => 0.3,
],
'competitive_pressure'=> [
'factor'            => 'Increased competitive pressure',
'impact_percentage' => 20,
'adjusted_benefit'  => $base_benefit * 1.20,
'probability'       => $this->calculate_competitive_pressure_probability( $enriched_profile ),
],
];
}

/**
 * Calculate confidence metrics for ROI projections.
 *
 * @param array $enriched_profile Enriched profile data.
 * @param array $user_inputs      Original user inputs.
 * @return array Confidence metrics.
 */
private function calculate_confidence_metrics( $enriched_profile, $user_inputs ) {
$data_quality                  = $this->assess_data_quality( $user_inputs );
$ai_confidence                 = $enriched_profile['enrichment_metadata']['confidence_level'] ?? 0.8;
$industry_benchmark_availability = $this->assess_benchmark_availability( $enriched_profile );

return [
'overall_confidence'        => min( $data_quality, $ai_confidence, $industry_benchmark_availability ),
'data_quality_score'        => $data_quality,
'ai_enrichment_confidence'  => $ai_confidence,
'industry_benchmark_confidence' => $industry_benchmark_availability,
'confidence_factors'        => [
'complete_operational_data'   => ! empty( $user_inputs['ftes'] ) && ! empty( $user_inputs['hours_reconciliation'] ),
'industry_context_available'  => ! empty( $enriched_profile['industry_context'] ),
'company_intelligence_depth'  => ! empty( $enriched_profile['company_profile']['enhanced_description'] ),
'financial_indicators_present'=> ! empty( $enriched_profile['company_profile']['financial_indicators'] ),
],
];
}

/**
 * Assess data quality score.
 *
 * @param array $user_inputs User supplied inputs.
 * @return float Score between 0 and 1.
 */
private function assess_data_quality( $user_inputs ) {
$required = [ 'ftes', 'hours_reconciliation', 'hours_cash_positioning' ];
$filled   = 0;
foreach ( $required as $field ) {
if ( ! empty( $user_inputs[ $field ] ) ) {
$filled++;
}
}

return $filled / count( $required );
}

/**
\t * Assess industry benchmark availability.
 *
 * @param array $enriched_profile Enriched profile data.
 * @return float Score between 0 and 1.
 */
private function assess_benchmark_availability( $enriched_profile ) {
return ! empty( $enriched_profile['industry_context']['benchmarks'] ) ? 1.0 : 0.5;
}

/**
 * Calculate probability of implementation delay.
 *
 * @param array $enriched_profile Enriched profile data.
 * @return float Probability value.
 */
private function calculate_delay_probability( $enriched_profile ) {
$complexity = $enriched_profile['strategic_insights']['implementation_complexity'] ?? 'medium';
$readiness  = $enriched_profile['company_profile']['treasury_maturity']['automation_readiness'] ?? 'medium';

$complexity_risk = [
'low'      => 0.1,
'medium'   => 0.2,
'high'     => 0.4,
'very_high'=> 0.6,
][ $complexity ] ?? 0.2;
$readiness_risk = [
'low'    => 0.3,
'medium' => 0.15,
'high'   => 0.05,
][ $readiness ] ?? 0.15;

return min( 0.8, $complexity_risk + $readiness_risk );
}

/**
 * Calculate probability of adoption resistance.
 *
 * @param array $enriched_profile Enriched profile data.
 * @return float Probability value.
 */
private function calculate_resistance_probability( $enriched_profile ) {
$culture = $enriched_profile['company_profile']['culture'] ?? 'neutral';
$map     = [
'innovative' => 0.1,
'neutral'    => 0.2,
'resistant'  => 0.4,
];

return $map[ $culture ] ?? 0.2;
}

/**
 * Calculate probability of competitive pressure.
 *
 * @param array $enriched_profile Enriched profile data.
 * @return float Probability value.
 */
private function calculate_competitive_pressure_probability( $enriched_profile ) {
$competition = $enriched_profile['industry_context']['competition_intensity'] ?? 'medium';
$map         = [
'low'    => 0.2,
'medium' => 0.3,
'high'   => 0.5,
];

return $map[ $competition ] ?? 0.3;
}
}

