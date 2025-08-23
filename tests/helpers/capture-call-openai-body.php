<?php
// Stubs for WordPress functions used in RTBCB_LLM
if ( ! function_exists( 'get_option' ) ) {
    function get_option( $name, $default = '' ) {
        if ( 'rtbcb_openai_api_key' === $name ) {
            return 'test-key';
        }
        return $default;
    }
}

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        public function __construct( $code = '', $message = '' ) {}
        public function get_error_message() { return ''; }
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) {
        return $thing instanceof WP_Error;
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data ) {
        return json_encode( $data );
    }
}

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = 'default' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
        return $text;
    }
}

$captured_body = null;
if ( ! function_exists( 'wp_remote_post' ) ) {
    function wp_remote_post( $url, $args ) {
        global $captured_body;
        $captured_body = json_decode( $args['body'], true );
        return [];
    }
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
    function wp_remote_retrieve_response_code( $response ) {
        return 200;
    }
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
    function wp_remote_retrieve_body( $response ) {
        return '{}';
    }
}

require_once __DIR__ . '/../../inc/class-rtbcb-llm.php';

$llm       = new RTBCB_LLM();
$ref       = new ReflectionClass( $llm );
$method    = $ref->getMethod( 'call_openai' );
$method->setAccessible( true );
$method->invoke( $llm, 'gpt-5', 'test prompt' );

global $captured_body;
echo json_encode( $captured_body );
