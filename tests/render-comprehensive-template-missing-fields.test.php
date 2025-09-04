
<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

defined( 'ABSPATH' ) || exit;

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

set_error_handler(
	function ( $errno, $errstr ) {
		echo "Unexpected warning: $errstr\n";
		exit( 1 );
	}
);

$report_data = [ 'company_name' => 'Demo Corp' ];

ob_start();
include __DIR__ . '/../templates/comprehensive-report-template.php';
ob_end_clean();

echo "Comprehensive template missing fields test passed.\n";

