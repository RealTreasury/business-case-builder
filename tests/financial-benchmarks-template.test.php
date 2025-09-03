<?php
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../inc/class-rtbcb-ajax.php';

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = null ) {
        return $text;
    }
}
if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $text, $domain = null ) {
        return $text;
    }
}
if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $text ) {
        return $text;
    }
}
if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $text ) {
        return $text;
    }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $text ) {
        return $text;
    }
}
if ( ! function_exists( 'current_time' ) ) {
    function current_time( $type ) {
        return '2024-01-01';
    }
}
if ( ! function_exists( 'rtbcb_get_analysis_type' ) ) {
    function rtbcb_get_analysis_type() {
        return 'test';
    }
}

$financial_benchmarks = [
    'industry_benchmarks' => [ [ 'metric' => 'EBITDA Margin', 'value' => '20%', 'source' => 'Report' ] ],
    'valuation_multiples' => [ [ 'metric' => 'P/E', 'range' => '15x-20x' ] ],
];

$user_inputs     = [ 'company_name' => 'Demo Corp', 'industry' => 'Finance' ];
$enriched_profile = [];
$roi_scenarios    = [ 'conservative' => [], 'base' => [], 'optimistic' => [] ];
$recommendation   = [ 'recommended' => '', 'category_info' => [] ];
$final_analysis   = [ 'financial_benchmarks' => $financial_benchmarks ];

$method = new ReflectionMethod( RTBCB_Ajax::class, 'structure_report_data' );
$method->setAccessible( true );
$report_data = $method->invoke( null, $user_inputs, $enriched_profile, $roi_scenarios, $recommendation, $final_analysis, [], [], microtime( true ), $financial_benchmarks );

ob_start();
include __DIR__ . '/../templates/comprehensive-report-template.php';
$output = ob_get_clean();

if ( strpos( $output, 'EBITDA Margin: 20%' ) === false || strpos( $output, 'P/E: 15x-20x' ) === false ) {
    echo "Financial benchmarks template render failed.\n";
    exit( 1 );
}

echo "Financial benchmarks template render test passed.\n";
