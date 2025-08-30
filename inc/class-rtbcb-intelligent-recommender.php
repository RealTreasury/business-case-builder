<?php
defined( 'ABSPATH' ) || exit;

/**
 * Intelligent recommender using AI insights for category suggestions.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */

/**
 * Class RTBCB_Intelligent_Recommender.
 */
class RTBCB_Intelligent_Recommender extends RTBCB_Category_Recommender {
/**
 * Generate recommendations using AI insights and rule-based scoring.
 *
 * @param array $user_inputs      Original user inputs.
 * @param array $enriched_profile AI-enriched company profile.
 * @return array Enhanced recommendation with confidence and alternatives.
 */
public function recommend_with_ai_insights( $user_inputs, $enriched_profile ) {
$base_recommendation = parent::recommend_category( $user_inputs );

$ai_factors      = $this->extract_ai_recommendation_factors( $enriched_profile );
$enhanced_scores = $this->apply_ai_insights_to_scoring(
$base_recommendation['scores'],
$ai_factors
);

arsort( $enhanced_scores );
$recommended = array_key_first( $enhanced_scores );

return [
'recommended' => $recommended,
'category_info' => self::get_category_info( $recommended ),
'scores' => $enhanced_scores,
'base_scores' => $base_recommendation['scores'],
'confidence' => $this->calculate_enhanced_confidence( $enhanced_scores, $enriched_profile ),
'reasoning' => $this->generate_ai_enhanced_reasoning( $recommended, $enriched_profile, $ai_factors ),
'alternatives' => $this->get_intelligent_alternatives( $enhanced_scores, $enriched_profile ),
'ai_insights' => [
'maturity_assessment'    => $ai_factors['maturity_alignment'],
'implementation_readiness' => $ai_factors['implementation_readiness'],
'strategic_fit'         => $ai_factors['strategic_alignment'],
'risk_assessment'       => $ai_factors['risk_factors'],
],
];
}

/**
 * Extract AI-based recommendation factors.
 *
 * @param array $enriched_profile Enriched profile data.
 * @return array
 */
private function extract_ai_recommendation_factors( $enriched_profile ) {
$company_profile    = $enriched_profile['company_profile'] ?? [];
$strategic_insights = $enriched_profile['strategic_insights'] ?? [];

return [
'maturity_alignment'      => $this->assess_maturity_category_alignment(
$company_profile['maturity_level'] ?? 'basic',
$company_profile['treasury_maturity'] ?? []
),
'implementation_readiness' => $this->assess_implementation_readiness(
$strategic_insights['technology_readiness'] ?? 'ready',
$company_profile['treasury_maturity']['automation_readiness'] ?? 'medium'
),
'strategic_alignment'     => $this->assess_strategic_category_alignment(
$strategic_insights,
$company_profile['strategic_context'] ?? []
),
'risk_factors'            => $this->assess_category_risk_factors(
$strategic_insights['potential_obstacles'] ?? [],
$enriched_profile['industry_context'] ?? []
),
'complexity_tolerance'    => $this->assess_complexity_tolerance(
$strategic_insights['implementation_complexity'] ?? 'medium',
$company_profile['financial_indicators'] ?? []
),
];
}

/**
 * Apply AI insights to modify category scores.
 *
 * @param array $base_scores Base category scores.
 * @param array $ai_factors  AI factors.
 * @return array
 */
private function apply_ai_insights_to_scoring( $base_scores, $ai_factors ) {
$enhanced_scores = $base_scores;
foreach ( $enhanced_scores as $category => &$score ) {
$maturity_adjustment    = $ai_factors['maturity_alignment'][ $category ] ?? 0;
$readiness_adjustment   = $ai_factors['implementation_readiness'][ $category ] ?? 0;
$strategic_adjustment   = $ai_factors['strategic_alignment'][ $category ] ?? 0;
$risk_penalty           = $ai_factors['risk_factors'][ $category ] ?? 0;

$score += $maturity_adjustment + $readiness_adjustment + $strategic_adjustment;
$score -= $risk_penalty;

if ( 'low' === $ai_factors['complexity_tolerance'] && 'trms' === $category ) {
$score *= 0.7;
} elseif ( 'high' === $ai_factors['complexity_tolerance'] && 'trms' === $category ) {
$score *= 1.2;
}

$score = max( 0, min( 100, $score ) );
}
unset( $score );

return $enhanced_scores;
}

/**
 * Generate AI-enhanced reasoning for recommendation.
 *
 * @param string $recommended      Recommended category slug.
 * @param array  $enriched_profile Enriched profile data.
 * @param array  $ai_factors       AI factor data.
 * @return string
 */
private function generate_ai_enhanced_reasoning( $recommended, $enriched_profile, $ai_factors ) {
$company_profile    = $enriched_profile['company_profile'] ?? [];
$strategic_insights = $enriched_profile['strategic_insights'] ?? [];

$reasoning_parts = [];
$maturity        = $company_profile['maturity_level'] ?? 'basic';
$maturity_reasoning = [
'basic'      => __( 'your current treasury maturity level indicates strong potential for foundational improvements', 'rtbcb' ),
'developing' => __( 'your evolving treasury operations are well-positioned for strategic technology adoption', 'rtbcb' ),
'strategic'  => __( 'your strategic treasury focus aligns with advanced technology capabilities', 'rtbcb' ),
'optimized'  => __( 'your mature treasury operations require sophisticated optimization tools', 'rtbcb' ),
];

if ( isset( $maturity_reasoning[ $maturity ] ) ) {
$reasoning_parts[] = $maturity_reasoning[ $maturity ];
}

if ( ! empty( $strategic_insights['expected_benefits']['strategic_value'] ) ) {
$reasoning_parts[] = sprintf(
__( 'the AI analysis indicates %s', 'rtbcb' ),
strtolower( $strategic_insights['expected_benefits']['strategic_value'] )
);
}

$readiness = $company_profile['treasury_maturity']['automation_readiness'] ?? 'medium';
$readiness_reasoning = [
'low'    => __( 'a phased implementation approach is recommended given current automation readiness', 'rtbcb' ),
'medium' => __( 'your moderate automation readiness supports a structured implementation timeline', 'rtbcb' ),
'high'   => __( 'your high automation readiness enables accelerated implementation and benefits realization', 'rtbcb' ),
];

if ( isset( $readiness_reasoning[ $readiness ] ) ) {
$reasoning_parts[] = $readiness_reasoning[ $readiness ];
}

return sprintf(
__( 'Based on the comprehensive analysis, %s.', 'rtbcb' ),
implode( ', and ', $reasoning_parts )
);
}

/**
 * Get intelligent alternatives based on AI insights.
 *
 * @param array $enhanced_scores Enhanced category scores.
 * @param array $enriched_profile Enriched profile data.
 * @return array
 */
private function get_intelligent_alternatives( $enhanced_scores, $enriched_profile ) {
$alternatives = [];
$sorted_scores = $enhanced_scores;
arsort( $sorted_scores );

$recommended = array_key_first( $sorted_scores );
foreach ( $sorted_scores as $category => $score ) {
if ( $category === $recommended || $score <= 60 ) {
continue;
}

$alternatives[] = [
'category' => $category,
'info'     => self::get_category_info( $category ),
'score'    => $score,
'reasoning' => $this->generate_alternative_reasoning( $category, $enriched_profile ),
'consideration_factors' => $this->get_alternative_consideration_factors( $category, $enriched_profile ),
];
}

return array_slice( $alternatives, 0, 2 );
}

/**
 * Assess implementation readiness impact per category.
 *
 * @param string $tech_readiness     Technology readiness level.
 * @param string $automation_readiness Automation readiness level.
 * @return array
 */
private function assess_implementation_readiness( $tech_readiness, $automation_readiness ) {
$adjustments = [ 'cash_tools' => 0, 'tms_lite' => 0, 'trms' => 0 ];
if ( 'ready' === $tech_readiness && 'high' === $automation_readiness ) {
$adjustments['trms'] = 10;
} elseif ( 'cautious' === $tech_readiness || 'low' === $automation_readiness ) {
$adjustments['trms'] = -10;
}
return $adjustments;
}

/**
 * Assess strategic category alignment.
 *
 * @param array $insights Strategic insights.
 * @param array $context  Strategic context.
 * @return array
 */
private function assess_strategic_category_alignment( $insights, $context ) {
$adjustments = [ 'cash_tools' => 0, 'tms_lite' => 0, 'trms' => 0 ];
if ( ( $insights['priority'] ?? '' ) === 'optimization' ) {
$adjustments['trms'] += 10;
}
if ( ( $context['growth_focus'] ?? '' ) === 'expansion' ) {
$adjustments['tms_lite'] += 5;
}
return $adjustments;
}

/**
 * Assess risk factors for each category.
 *
 * @param array $obstacles       Potential obstacles.
 * @param array $industry_context Industry context data.
 * @return array
 */
private function assess_category_risk_factors( $obstacles, $industry_context ) {
$penalties = [ 'cash_tools' => 0, 'tms_lite' => 0, 'trms' => 0 ];
if ( in_array( 'budget', $obstacles, true ) ) {
$penalties['trms'] += 15;
}
if ( ( $industry_context['regulation_level'] ?? '' ) === 'high' ) {
$penalties['cash_tools'] += 5;
}
return $penalties;
}

/**
 * Assess complexity tolerance.
 *
 * @param string $implementation_complexity Expected implementation complexity.
 * @param array  $financial_indicators     Financial indicators.
 * @return string
 */
private function assess_complexity_tolerance( $implementation_complexity, $financial_indicators ) {
if ( 'high' === $implementation_complexity && ( $financial_indicators['budget_flexibility'] ?? 'medium' ) === 'low' ) {
return 'low';
}
if ( 'low' === $implementation_complexity ) {
return 'high';
}
return 'medium';
}

/**
 * Generate alternative reasoning text.
 *
 * @param string $category         Category slug.
 * @param array  $enriched_profile Enriched profile data.
 * @return string
 */
private function generate_alternative_reasoning( $category, $enriched_profile ) {
$company_profile = $enriched_profile['company_profile'] ?? [];
return sprintf(
__( '%s could also address your needs given current company characteristics', 'rtbcb' ),
self::get_category_info( $category )['name']
);
}

/**
 * Get alternative consideration factors.
 *
 * @param string $category         Category slug.
 * @param array  $enriched_profile Enriched profile data.
 * @return array
 */
private function get_alternative_consideration_factors( $category, $enriched_profile ) {
return [
'automation_readiness' => $enriched_profile['company_profile']['treasury_maturity']['automation_readiness'] ?? 'medium',
'financial_health'     => $enriched_profile['company_profile']['financial_indicators']['financial_health'] ?? 'stable',
];
}

/**
 * Calculate enhanced confidence score.
 *
 * @param array $scores            Enhanced scores.
 * @param array $profile           Enriched profile data.
 * @return float
 */
private function calculate_enhanced_confidence( $scores, $profile ) {
$values = array_values( $scores );
$top    = $values[0] ?? 0;
$second = $values[1] ?? 0;

$diff = $top - $second;
return max( 0.5, min( 1, $diff / 100 ) );
}
}

