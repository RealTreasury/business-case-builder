<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = '' ) {
		return $default;
	}
}

require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public function __construct( $code = '', $message = '' ) {}
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		return $text;
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
		return preg_replace( '/[^a-z0-9_\-]/', '', $key );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title ) {
		$title = strtolower( $title );
		$title = preg_replace( '/[^a-z0-9]+/', '-', $title );
		return trim( $title, '-' );
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $name, $value, $expiration ) {}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $name ) {
		return false;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value ) {
		return $value;
	}
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data ) {
		return json_encode( $data );
	}
}

$llm = new RTBCB_LLM();

$method = new ReflectionMethod( RTBCB_LLM::class, 'project_growth_path' );
$method->setAccessible( true );
$result = $method->invoke( $llm, '$50M-$500M', 'technology' );

if ( 'scaling trajectory' !== $result['size_outlook'] ) {
	echo "Unexpected size outlook\n";
	exit( 1 );
}

if ( 'rapid expansion' !== $result['industry_outlook'] ) {
	echo "Unexpected industry outlook\n";
	exit( 1 );
}

$research_method = new ReflectionMethod( RTBCB_LLM::class, 'conduct_company_research' );
$research_method->setAccessible( true );
$research = $research_method->invoke( $llm, [
	'company_name' => 'Test Co',
	'company_size' => '$50M-$500M',
	'industry'     => 'technology',
] );

if ( ! is_array( $research ) ) {
	echo "Research did not return array\n";
	exit( 1 );
}

if ( 'scaling trajectory' !== $research['growth_trajectory']['size_outlook'] ) {
	echo "Growth trajectory mismatch\n";
	exit( 1 );
}

$prop = new ReflectionProperty( RTBCB_LLM::class, 'last_company_research' );
$prop->setAccessible( true );
$serialized = $prop->getValue( $llm );

if ( ! is_string( $serialized ) ) {
	echo "Serialized research missing\n";
	exit( 1 );
}

$decoded = json_decode( $serialized, true );

if ( 'rapid expansion' !== $decoded['growth_trajectory']['industry_outlook'] ) {
	echo "Serialized research mismatch\n";
	exit( 1 );
}

echo "project-growth-path.test.php passed\n";
