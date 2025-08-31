<?php
define( 'ABSPATH', __DIR__ );

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
if ( ! function_exists( 'number_format_i18n' ) ) {
	function number_format_i18n( $number, $decimals = 0 ) {
	    return number_format( $number, $decimals );
	}
}

$business_case_data = [
	'narrative' => 'Quick overview.',
	'roi_base'  => 123456,
];

ob_start();
include __DIR__ . '/../templates/fast-report-template.php';
$output = ob_get_clean();

if ( strpos( $output, 'ROI Summary' ) === false ) {
	echo "ROI Summary not found\n";
	exit( 1 );
}

echo "Fast template render test passed.\n";
