<?php
defined( 'ABSPATH' ) || exit;

/**
 * Intelligent Recommender that uses AI insights for category recommendations.
 *
 * @package RealTreasuryBusinessCaseBuilder
 */
class RTBCB_Intelligent_Recommender extends RTBCB_Category_Recommender {
/**
 * Generate recommendations using AI insights and rule-based scoring.
 *
 * @param array $user_inputs	  Original user inputs.
 * @param array $enriched_profile AI-enriched company profile.
 * @return array Enhanced recommendation with confidence and alternatives.
 */
public function recommend_with_ai_insights( $user_inputs, $enriched_profile ) {
// Get baseline recommendation from parent class.
$base_recommendation = parent::recommend_category( $user_inputs );

// Apply AI insights to enhance recommendation.
$ai_factors		 = $this->extract_ai_recommendation_factors( $enriched_profile );
$enhanced_scores = $this->apply_ai_insights_to_scoring(
$base_recommendation['scores'],
$ai_factors
);

// Recalculate recommendation with enhanced scores.
arsort( $enhanced_scores );
$recommended = array_key_first( $enhanced_scores );

return [
'recommended'	=> $recommended,
'category_info' => self::get_category_info( $recommended ),
'scores'		=> $enhanced_scores,
'base_scores'	=> $base_recommendation['scores'],
'confidence'	=> $this->calculate_enhanced_confidence( $enhanced_scores, $enriched_profile ),
'reasoning'		=> $this->generate_ai_enhanced_reasoning( $recommended, $enriched_profile, $ai_factors ),
'alternatives'	=> $this->get_intelligent_alternatives( $enhanced_scores, $enriched_profile ),
'ai_insights'	=> [
'maturity_assessment'	 => $ai_factors['maturity_alignment'],
'implementation_readiness'=> $ai_factors['implementation_readiness'],
'strategic_fit'			 => $ai_factors['strategic_alignment'],
'risk_assessment'		 => $ai_factors['risk_factors'],
],
];
}

/**
 * Extract AI-based recommendation factors.
 *
 * @param array $enriched_profile Enriched profile data.
 * @return array AI factors for scoring.
 */
private function extract_ai_recommendation_factors( $enriched_profile ) {
$company_profile   = $enriched_profile['company_profile'] ?? [];
$strategic_insight = $enriched_profile['strategic_insights'] ?? [];

return [
'maturity_alignment'	 => $this->assess_maturity_category_alignment(
$company_profile['maturity_level'] ?? 'basic',
$company_profile['treasury_maturity'] ?? []
),
'implementation_readiness' => $this->assess_implementation_readiness(
$strategic_insight['technology_readiness'] ?? 'ready',
$company_profile['treasury_maturity']['automation_readiness'] ?? 'medium'
),
'strategic_alignment'	 => $this->assess_strategic_category_alignment(
$strategic_insight,
$company_profile['strategic_context'] ?? []
),
'risk_factors'			 => $this->assess_category_risk_factors(
$strategic_insight['potential_obstacles'] ?? [],
$enriched_profile['industry_context'] ?? []
),
'complexity_tolerance'	 => $this->assess_complexity_tolerance(
$strategic_insight['implementation_complexity'] ?? 'medium',
$company_profile['financial_indicators'] ?? []
),
];
}

/**
 * Apply AI insights to modify category scores.
 *
 * @param array $base_scores Base scores from recommender.
 * @param array $ai_factors	 AI factor adjustments.
 * @return array Adjusted scores.
 */
private function apply_ai_insights_to_scoring( $base_scores, $ai_factors ) {
$enhanced_scores = $base_scores;

foreach ( $enhanced_scores as $category => &$score ) {
$maturity_adjustment	= $ai_factors['maturity_alignment'][ $category ] ?? 0;
$score					+= $maturity_adjustment;
$readiness_adjustment	= $ai_factors['implementation_readiness'][ $category ] ?? 0;
$score					+= $readiness_adjustment;
$strategic_adjustment	= $ai_factors['strategic_alignment'][ $category ] ?? 0;
$score					+= $strategic_adjustment;
$risk_penalty			= $ai_factors['risk_factors'][ $category ] ?? 0;
$score					-= $risk_penalty;

if ( 'low' === $ai_factors['complexity_tolerance'] ) {
if ( 'trms' === $category ) {
$score *= 0.7;
}
} elseif ( 'high' === $ai_factors['complexity_tolerance'] ) {
if ( 'trms' === $category ) {
$score *= 1.2;
}
}

$score = max( 0, min( 100, $score ) );
}

return $enhanced_scores;
}

/**
 * Assess how well each category aligns with company maturity.
 *
 * @param string $maturity_level Company maturity level.
 * @param array	 $treasury_maturity Treasury maturity details.
 * @return array Category alignment adjustments.
 */
private function assess_maturity_category_alignment( $maturity_level, $treasury_maturity ) {
$sophistication = $treasury_maturity['sophistication_level'] ?? 'manual';
unset( $sophistication ); // Currently unused, reserved for future logic.

$alignments = [
'basic'		 => [
'cash_tools' => 15,
'tms_lite'	 => 5,
'trms'		 => -10,
],
'developing' => [
'cash_tools' => 5,
'tms_lite'	 => 15,
'trms'		 => -5,
],
'strategic'	 => [
'cash_tools' => -5,
'tms_lite'	 => 10,
'trms'		 => 15,
],
'optimized'	 => [
'cash_tools' => -15,
'tms_lite'	 => 0,
'trms'		 => 10,
],
];

return $alignments[ $maturity_level ] ?? $alignments['basic'];
}

/**
 * Generate AI-enhanced reasoning for recommendation.
 *
 * @param string $recommended	  Recommended category key.
 * @param array	 $enriched_profile Enriched profile data.
 * @param array	 $ai_factors	   AI factors used.
 * @return string Reasoning text.
 */
private function generate_ai_enhanced_reasoning( $recommended, $enriched_profile, $ai_factors ) {
$company_profile   = $enriched_profile['company_profile'] ?? [];
$strategic_insight = $enriched_profile['strategic_insights'] ?? [];
unset( $ai_factors ); // Reserved for future usage.

$reasoning_parts = [];
$maturity		 = $company_profile['maturity_level'] ?? 'basic';
$maturity_reason = [
'basic'		 => __( 'your current treasury maturity level indicates strong potential for foundational improvements', 'rtbcb' ),
'developing' => __( 'your evolving treasury operations are well-positioned for strategic technology adoption', 'rtbcb' ),
'strategic'	 => __( 'your strategic treasury focus aligns with advanced technology capabilities', 'rtbcb' ),
'optimized'	 => __( 'your mature treasury operations require sophisticated optimization tools', 'rtbcb' ),
];
if ( isset( $maturity_reason[ $maturity ] ) ) {
$reasoning_parts[] = $maturity_reason[ $maturity ];
}

if ( ! empty( $strategic_insight['expected_benefits']['strategic_value'] ) ) {
$reasoning_parts[] = sprintf(
__( 'the AI analysis indicates %s', 'rtbcb' ),
strtolower( $strategic_insight['expected_benefits']['strategic_value'] )
);
}

$readiness			= $company_profile['treasury_maturity']['automation_readiness'] ?? 'medium';
$readiness_reasoning = [
'low'	 => __( 'a phased implementation approach is recommended given current automation readiness', 'rtbcb' ),
'medium' => __( 'your moderate automation readiness supports a structured implementation timeline', 'rtbcb' ),
'high'	 => __( 'your high automation readiness enables accelerated implementation and benefits realization', 'rtbcb' ),
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
 * @param array $enhanced_scores  Score map of categories.
 * @param array $enriched_profile Enriched profile data.
 * @return array Alternative recommendations.
 */
private function get_intelligent_alternatives( $enhanced_scores, $enriched_profile ) {
$alternatives  = [];
$sorted_scores = $enhanced_scores;
arsort( $sorted_scores );
$recommended = array_key_first( $sorted_scores );

foreach ( $sorted_scores as $category => $score ) {
if ( $category !== $recommended && $score > 60 ) {
$alternatives[] = [
'category'			   => $category,
'info'				   => self::get_category_info( $category ),
'score'				   => $score,
'reasoning'			   => $this->generate_alternative_reasoning( $category, $enriched_profile ),
'consideration_factors'=> $this->get_alternative_consideration_factors( $category, $enriched_profile ),
];
}
}

return array_slice( $alternatives, 0, 2 );
}

/** Additional helper methods. */
private function assess_implementation_readiness( $tech_readiness, $automation_readiness ) {
$map = [
'cash_tools' => 0,
'tms_lite'	 => 0,
'trms'		 => 0,
];
unset( $tech_readiness, $automation_readiness );
return $map;
}

private function assess_strategic_category_alignment( $insights, $context ) {
unset( $insights, $context );
return [ 'cash_tools' => 0, 'tms_lite' => 0, 'trms' => 0 ];
}

private function calculate_enhanced_confidence( $scores, $profile ) {
unset( $profile );
$max   = max( $scores );
$min   = min( $scores );
$range = $max - $min;
return $range > 0 ? 1 - ( $range / 100 ) : 0.5;
}

private function generate_alternative_reasoning( $category, $enriched_profile ) {
unset( $enriched_profile );
return sprintf( __( 'consider %s based on secondary fit factors', 'rtbcb' ), $category );
}

private function get_alternative_consideration_factors( $category, $enriched_profile ) {
unset( $category, $enriched_profile );
return [];
}

private function assess_category_risk_factors( $obstacles, $context ) {
unset( $obstacles, $context );
return [ 'cash_tools' => 0, 'tms_lite' => 0, 'trms' => 0 ];
}

private function assess_complexity_tolerance( $complexity, $financials ) {
unset( $financials );
return $complexity;
}
}

