<?php
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
        'strategic_positioning'   => 'pos',
        'business_case_strength'  => 'Strong',
        'key_value_drivers'       => [ 'driver' ],
        'executive_recommendation'=> 'rec',
        'confidence_level'        => 0.9,
    ],
    'operational_analysis' => [
        'current_state_assessment' => [
            'efficiency_rating'    => 'Good',
            'benchmark_comparison' => 'peer',
            'capacity_utilization' => 'high',
        ],
        'process_inefficiencies'  => [],
        'automation_opportunities'=> [],
    ],
    'industry_insights' => [
        'sector_trends'          => 'trend',
        'competitive_benchmarks' => 'bench',
        'regulatory_considerations' => 'reg',
    ],
    'technology_recommendations' => [
        'primary_solution' => [
            'category'     => 'cat',
            'rationale'    => 'why',
            'key_features' => [ 'feature' ],
        ],
        'implementation_approach' => [
            'phase_1'        => 'p1',
            'phase_2'        => 'p2',
            'success_metrics'=> [ 'metric' ],
        ],
    ],
    'financial_analysis' => [
        'investment_breakdown' => [
            'software_licensing'      => 'cost',
            'implementation_services' => 'cost',
            'training_change_management' => 'cost',
        ],
        'payback_analysis' => [
            'payback_months' => 12,
            'roi_3_year'     => 50,
            'npv_analysis'   => 'npv',
        ],
    ],
    'risk_mitigation' => [
        'implementation_risks' => [ 'risk' ],
        'mitigation_strategies' => [
            'risk_1_mitigation' => 'mit1',
            'risk_2_mitigation' => 'mit2',
        ],
    ],
    'next_steps' => [ 'step' ],
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

$required = [ 'executive_summary', 'operational_analysis', 'industry_insights', 'technology_recommendations', 'financial_analysis', 'risk_mitigation', 'next_steps' ];

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

