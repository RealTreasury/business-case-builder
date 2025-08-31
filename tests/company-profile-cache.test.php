<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $text ) {
		$text = is_scalar( $text ) ? (string) $text : '';
		$text = preg_replace( '/[\r\n\t\0\x0B]/', '', $text );
		return trim( $text );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title ) {
		$title = strtolower( $title );
		$title = preg_replace( '/[^a-z0-9]+/', '-', $title );
		return trim( $title, '-' );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		$key = strtolower( $key );
		return preg_replace( '/[^a-z0-9_\-]/', '', $key );
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $name, $value, $expiration ) {
		global $transients;
		$transients[ $name ] = $value;
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $name ) {
		global $transients;
		return $transients[ $name ] ?? false;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = '' ) {
		return $default;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value ) {
		return $value;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		return $text;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data ) {
		return json_encode( $data );
	}
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

$llm = new RTBCB_LLM();
$method = new ReflectionMethod( RTBCB_LLM::class, 'conduct_company_research' );
$method->setAccessible( true );

$inputs = [
	'company_name' => 'Cache Co',
	'company_size' => '$50M-$500M',
	'industry'     => 'finance',
];

$result = $method->invoke( $llm, $inputs );

if ( ! is_array( $result['company_profile'] ) ) {
	echo "Profile not returned\n";
	exit( 1 );
}

$size_key = sanitize_title( $inputs['company_size'] );
$cached = rtbcb_get_research_cache( 'Cache Co', 'finance', 'company_profile_' . $size_key );
if ( false === $cached ) {
	echo "Profile not cached\n";
	exit( 1 );
}

$custom = [ 'stage' => 'cached', 'characteristics' => '', 'treasury_focus' => '', 'typical_challenges' => '' ];
rtbcb_set_research_cache( 'Cache Co', 'finance', 'company_profile_' . $size_key, $custom );

$result2 = $method->invoke( $llm, $inputs );
if ( 'cached' !== ( $result2['company_profile']['stage'] ?? '' ) ) {
	echo "Cache not used\n";
	exit( 1 );
}

$inputs2 = $inputs;
$inputs2['company_size'] = '$500M-$1B';
$result3 = $method->invoke( $llm, $inputs2 );
if ( 'cached' === ( $result3['company_profile']['stage'] ?? '' ) ) {
	echo "Cache used across sizes\n";
	exit( 1 );
}

echo "company-profile-cache.test.php passed\n";
