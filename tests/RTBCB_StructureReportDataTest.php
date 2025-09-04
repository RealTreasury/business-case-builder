<?php
if ( ! defined( 'ABSPATH' ) ) {
define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/wp-stubs.php';
require_once __DIR__ . '/../inc/class-rtbcb-ajax.php';
require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

if ( ! function_exists( '__' ) ) {
function __( $text, $domain = null ) {
return $text;
}
}
if ( ! function_exists( 'current_time' ) ) {
function current_time( $format ) {
return '2024-01-01';
}
}
if ( ! function_exists( 'rtbcb_get_analysis_type' ) ) {
function rtbcb_get_analysis_type() {
return 'test';
}
}

final class RTBCB_StructureReportDataTest extends TestCase {
public function test_non_array_fields_become_empty_arrays() {
$method = new ReflectionMethod( RTBCB_Ajax::class, 'structure_report_data' );
$method->setAccessible( true );

$user_inputs = [
'company_name' => 'Test Co',
];

$enriched_profile = [
'company_profile'   => [
'treasury_maturity'    => 'foo',
'key_challenges'       => null,
'strategic_priorities' => 5,
],
'maturity_assessment' => 'bar',
'competitive_position' => null,
];

$roi_scenarios = [
'sensitivity_analysis' => 'baz',
'confidence_metrics'   => null,
'conservative'         => [],
'base'                 => [],
'optimistic'           => [],
];

$recommendation = [
'recommended'   => 'cat',
'category_info' => 'foo',
];

$final_analysis = [
'financial_analysis'   => [
'investment_breakdown' => 'foo',
'payback_analysis'     => null,
],
'implementation_roadmap' => 'bar',
'vendor_considerations'  => 'baz',
'risk_analysis'          => [
'mitigation_strategies' => 'foo',
'success_factors'      => null,
],
];

$result = $method->invoke( null, $user_inputs, $enriched_profile, $roi_scenarios, $recommendation, $final_analysis, [], [], microtime( true ), [] );

$profile   = $result['company_intelligence']['enriched_profile'];
$company   = $result['company_intelligence'];
$financial = $result['financial_analysis'];
$tech      = $result['technology_strategy'];
$risk      = $result['risk_analysis'];

self::assertSame( [], $profile['treasury_maturity'] );
self::assertSame( [], $profile['key_challenges'] );
self::assertSame( [], $profile['strategic_priorities'] );
self::assertSame( [], $company['maturity_assessment'] );
self::assertSame( [], $company['competitive_position'] );
self::assertSame( [], $financial['investment_breakdown'] );
self::assertSame( [], $financial['payback_analysis'] );
self::assertSame( [], $financial['sensitivity_analysis'] );
self::assertSame( [], $financial['confidence_metrics'] );
self::assertSame( [], $tech['category_details'] );
self::assertSame( [], $tech['implementation_roadmap'] );
self::assertSame( [], $tech['vendor_considerations'] );
self::assertSame( [], $risk['mitigation_strategies'] );
self::assertSame( [], $risk['success_factors'] );
}

public function test_industry_insights_preserved() {
$method = new ReflectionMethod( RTBCB_Ajax::class, 'structure_report_data' );
$method->setAccessible( true );

$user_inputs     = [ 'company_name' => 'Test', 'industry' => 'finance' ];
$enriched_profile = [];
$roi_scenarios    = [ 'conservative' => [], 'base' => [], 'optimistic' => [] ];
$recommendation   = [ 'recommended' => '', 'category_info' => [] ];
$analysis_data    = [
'executive_summary' => [],
'operational_insights' => [],
'industry_insights' => [
'sector_trends'            => [ 'trend' ],
'competitive_benchmarks'   => [ 'benchmark' ],
'regulatory_considerations' => [ 'regulation' ],
],
];

$llm_reflection  = new ReflectionClass( RTBCB_LLM::class );
$llm             = $llm_reflection->newInstanceWithoutConstructor();
$analysis_method = new ReflectionMethod( RTBCB_LLM::class, 'validate_and_structure_analysis' );
$analysis_method->setAccessible( true );
$final_analysis  = $analysis_method->invoke( $llm, $analysis_data );

$result = $method->invoke( null, $user_inputs, $enriched_profile, $roi_scenarios, $recommendation, $final_analysis, [], microtime( true ), [] );

self::assertSame( [ 'trend' ], $final_analysis['industry_insights']['sector_trends'] );
self::assertSame( $final_analysis['industry_insights'], $result['industry_insights'] );
}

public function test_operational_insights_retained() {
$method = new ReflectionMethod( RTBCB_Ajax::class, 'structure_report_data' );
$method->setAccessible( true );

$user_inputs     = [ 'company_name' => 'Test', 'industry' => 'finance' ];
$enriched_profile = [];
$roi_scenarios    = [ 'conservative' => [], 'base' => [], 'optimistic' => [] ];
$recommendation   = [ 'recommended' => '', 'category_info' => [] ];
$analysis_data    = [
'operational_insights' => [
'current_state_assessment' => [ 'Manual process', 'Siloed data' ],
'process_improvements'     => [
[
'process_area'   => 'Reconciliation',
'current_state'  => 'Manual spreadsheets',
'improved_state' => 'Automated workflow',
'impact_level'   => 'High',
],
],
'automation_opportunities' => [
[
'opportunity'           => 'Cash Forecasting',
'complexity'            => 'Medium',
'time_savings'          => 8,
'implementation_effort' => 'Low',
],
],
],
];

$llm_reflection  = new ReflectionClass( RTBCB_LLM::class );
$llm             = $llm_reflection->newInstanceWithoutConstructor();
$analysis_method = new ReflectionMethod( RTBCB_LLM::class, 'validate_and_structure_analysis' );
$analysis_method->setAccessible( true );
$final_analysis  = $analysis_method->invoke( $llm, $analysis_data );

$result = $method->invoke( null, $user_inputs, $enriched_profile, $roi_scenarios, $recommendation, $final_analysis, [], microtime( true ), [] );

self::assertSame( [ 'Manual process', 'Siloed data' ], $result['operational_insights']['current_state_assessment'] );
self::assertSame(
	[ 'Reconciliation: Manual spreadsheets → Automated workflow (High impact)' ],
	$result['operational_insights']['process_improvements']
);
self::assertSame(
	[ 'Cash Forecasting: Medium complexity, Low effort → 8 hours saved' ],
	$result['operational_insights']['automation_opportunities']
);
}

public function test_empty_operational_arrays_default_to_no_data() {
	$method = new ReflectionMethod( RTBCB_Ajax::class, 'structure_report_data' );
	$method->setAccessible( true );

	$user_inputs     = [ 'company_name' => 'Test', 'industry' => 'finance' ];
	$enriched_profile = [];
	$roi_scenarios    = [ 'conservative' => [], 'base' => [], 'optimistic' => [] ];
	$recommendation   = [ 'recommended' => '', 'category_info' => [] ];
	$analysis_data    = [
		'operational_insights' => [
			'process_improvements'     => [ [] ],
			'automation_opportunities' => [ [] ],
		],
	];

	$llm_reflection  = new ReflectionClass( RTBCB_LLM::class );
	$llm             = $llm_reflection->newInstanceWithoutConstructor();
	$analysis_method = new ReflectionMethod( RTBCB_LLM::class, 'validate_and_structure_analysis' );
	$analysis_method->setAccessible( true );
	$final_analysis  = $analysis_method->invoke( $llm, $analysis_data );

	$result = $method->invoke( null, $user_inputs, $enriched_profile, $roi_scenarios, $recommendation, $final_analysis, [], microtime( true ), [] );

	self::assertSame( [ 'No data provided' ], $result['operational_insights']['process_improvements'] );
	self::assertSame( [ 'No data provided' ], $result['operational_insights']['automation_opportunities'] );
}

public function test_financial_benchmarks_pass_through() {
$method = new ReflectionMethod( RTBCB_Ajax::class, 'structure_report_data' );
$method->setAccessible( true );

$user_inputs     = [ 'company_name' => 'Test', 'industry' => 'finance' ];
$enriched_profile = [];
$roi_scenarios    = [ 'conservative' => [], 'base' => [], 'optimistic' => [] ];
$recommendation   = [ 'recommended' => '', 'category_info' => [] ];
$financial_benchmarks = [
'industry_benchmarks' => [ [ 'metric' => 'EBITDA Margin', 'value' => '20%', 'source' => 'Report' ] ],
'valuation_multiples' => [ [ 'metric' => 'P/E', 'range' => '10x-12x' ] ],
];
$final_analysis   = [ 'financial_benchmarks' => $financial_benchmarks ];

$result = $method->invoke( null, $user_inputs, $enriched_profile, $roi_scenarios, $recommendation, $final_analysis, [], [], microtime( true ), $financial_benchmarks );

self::assertSame( $financial_benchmarks, $result['financial_benchmarks'] );
}

public function test_rag_context_pass_through() {
	$method = new ReflectionMethod( RTBCB_Ajax::class, 'structure_report_data' );
	$method->setAccessible( true );

	$user_inputs     = [ 'company_name' => 'Test', 'industry' => 'finance' ];
	$enriched_profile = [];
	$roi_scenarios    = [ 'conservative' => [], 'base' => [], 'optimistic' => [] ];
	$recommendation   = [ 'recommended' => '', 'category_info' => [] ];
	$final_analysis   = [];
	$rag_context      = [ 'ctx item' ];

	$result = $method->invoke( null, $user_inputs, $enriched_profile, $roi_scenarios, $recommendation, $final_analysis, $rag_context, [], microtime( true ), [] );

	self::assertSame( $rag_context, $result['rag_context'] );
}
}

