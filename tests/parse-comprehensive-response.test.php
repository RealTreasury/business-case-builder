<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $errors = [];
		public function __construct( $code = '', $message = '' ) {
			$this->errors[ $code ] = [ $message ];
		}
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		return $text;
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = '' ) {
		return $default;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $text ) {
		$text = is_scalar( $text ) ? (string) $text : '';
		$text = preg_replace( '/[\r\n\t\0\x0B]/', '', $text );
		return trim( $text );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		$key = strtolower( $key );
		return preg_replace( '/[^a-z0-9_]/', '', $key );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		return $response['body'] ?? '';
	}
}

$llm    = new RTBCB_LLM();
$method = new ReflectionMethod( RTBCB_LLM::class, 'parse_comprehensive_response' );
$method->setAccessible( true );

$valid_json = [
        'executive_summary' => [
                'strategic_positioning'    => 'pos',
                'business_case_strength'   => 'Strong',
                'key_value_drivers'        => [ 'driver' ],
                'executive_recommendation' => 'rec',
                'confidence_level'         => 0.9,
        ],
        'company_intelligence' => [
                'enriched_profile' => [
                        'name'                => 'Corp',
                        'industry'            => 'Finance',
                        'size'                => 'Mid',
                        'maturity_level'      => 'intermediate',
                        'key_challenges'      => [ 'challenge' ],
                        'strategic_priorities'=> [ 'priority' ],
                ],
                'industry_context' => [
                        'competitive_pressure'   => 'high',
                        'regulatory_environment' => 'strict',
                        'sector_trends'          => 'growth',
                ],
                'maturity_assessment' => [ 'stage' => 'intermediate' ],
                'competitive_position' => [ 'rank' => '2nd' ],
        ],
        'operational_insights' => [
                'current_state_assessment' => [ 'state' ],
                'process_improvements'     => [ 'improve' ],
                'automation_opportunities' => [ 'auto' ],
        ],
        'risk_analysis' => [
                'implementation_risks' => [ 'risk' ],
                'mitigation_strategies' => [ 'mitigate' ],
                'success_factors'       => [ 'factor' ],
        ],
        'action_plan' => [
                'immediate_steps'      => [ 'step1' ],
                'short_term_milestones'=> [ 'mile1' ],
                'long_term_objectives' => [ 'obj1' ],
        ],
        'industry_insights' => [
                'sector_trends'            => [ 'trend1' ],
                'competitive_benchmarks'   => [ 'bench1' ],
                'regulatory_considerations'=> [ 'reg1' ],
        ],
        'technology_strategy' => [
                'recommended_category' => 'cat',
                'category_details'     => [ 'detail' ],
                'implementation_roadmap' => [
                        [
                                'phase'      => 'p1',
                                'timeline'   => 'Q1',
                                'activities' => [ 'step1' ],
                        ],
                ],
                'vendor_considerations' => [ 'vendor1' ],
        ],
        'financial_analysis' => [
                'roi_scenarios' => [ [ 'scenario' => 'base', 'roi' => 10, 'total_annual_benefit' => 1000 ] ],
                'investment_breakdown' => [ [ 'category' => 'capex', 'amount' => 500 ] ],
                'payback_analysis' => [
                        'payback_months' => 12,
                        'roi_3_year'     => 50,
                        'npv_analysis'   => 'npv',
                ],
                'sensitivity_analysis' => [ [ 'factor' => 'growth', 'impact' => 'high' ] ],
                'chart_data' => [ 1, 2, 3 ],
        ],
];

$response = [
	'body' => json_encode( [
		'output_text' => json_encode( $valid_json ),
	] ),
];

$result = $method->invoke( $llm, $response );

if ( is_wp_error( $result ) ) {
	echo "Valid response produced WP_Error\n";
	exit( 1 );
}

$required = [
        'executive_summary',
        'company_intelligence',
        'operational_insights',
        'risk_analysis',
        'action_plan',
        'industry_insights',
        'technology_strategy',
        'financial_analysis',
        'implementation_roadmap',
];

foreach ( $required as $key ) {
	if ( ! isset( $result[ $key ] ) ) {
		echo "Missing expected key: {$key}\n";
		exit( 1 );
	}
}

$bad_response = [ 'body' => 'not json' ];
$bad_result   = $method->invoke( $llm, $bad_response );

if ( ! is_wp_error( $bad_result ) ) {
	echo "Invalid response did not return WP_Error\n";
	exit( 1 );
}

echo "parse-comprehensive-response.test.php passed\n";

