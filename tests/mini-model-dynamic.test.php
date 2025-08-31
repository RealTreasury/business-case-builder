<?php
require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = '' ) {
		if ( 'rtbcb_openai_api_key' === $name ) {
			return 'test-key';
		}
		if ( 'rtbcb_mini_model' === $name ) {
			return 'dynamic-mini';
		}
		return $default;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		$key = strtolower( $key );
		return preg_replace( '/[^a-z0-9_]/', '', $key );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		return $text;
	}
}

$llm    = new RTBCB_LLM();
$method = new ReflectionMethod( RTBCB_LLM::class, 'get_model' );
$method->setAccessible( true );
$model = $method->invoke( $llm, 'mini' );

if ( 'dynamic-mini' !== $model ) {
	echo "Mini model did not use configuration\n";
	exit( 1 );
}

echo "mini-model-dynamic.test.php passed\n";

