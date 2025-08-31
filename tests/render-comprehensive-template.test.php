<?php
require_once __DIR__ . '/../inc/helpers.php';

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
if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type ) {
		return '2024-01-01';
	}
}

$business_case_data = [
	'company_name'		=> 'Demo Corp',
	'executive_summary' => [
		'strategic_positioning'	  => 'Positioned well.',
		'business_case_strength'  => 'Strong',
		'key_value_drivers'		  => [ 'Efficiency', 'Compliance' ],
		'executive_recommendation'=> 'Proceed',
	],
];

ob_start();
include __DIR__ . '/../templates/comprehensive-report-template.php';
$output = ob_get_clean();

if ( strpos( $output, 'rtbcb-executive-summary' ) === false ) {
	echo "Executive summary not found\n";
	exit( 1 );
}

echo "Comprehensive template render test passed.\n";
