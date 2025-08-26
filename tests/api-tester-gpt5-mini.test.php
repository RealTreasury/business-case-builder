<?php
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = null ) {
        return $text;
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $name, $default = '' ) {
        return $default;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( $name, $value ) {}
}

if ( ! function_exists( 'delete_transient' ) ) {
    function delete_transient( $name ) {}
}

if ( ! function_exists( 'set_transient' ) ) {
    function set_transient( $name, $value, $expiration ) {}
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {}
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) {
        return false;
    }
}

require_once __DIR__ . '/helpers/wp-http.php';
require_once __DIR__ . '/../inc/enhanced-ajax-handlers.php';

$api_key = getenv( 'OPENAI_API_KEY' );
if ( empty( $api_key ) ) {
    echo "api-tester-gpt5-mini.test.php incomplete: missing OPENAI_API_KEY\n";
    exit( 0 );
}

$result = RTBCB_API_Tester::test_connection( $api_key );
if ( empty( $result['success'] ) ) {
    echo "API connection test failed\n";
    exit( 1 );
}

$status_code = $result['details']['status_code'] ?? 0;
$model_count = $result['details']['model_count'] ?? 0;
$rate_limits = $result['details']['rate_limits'] ?? null;

if ( 200 !== $status_code ) {
    echo "Unexpected status code: $status_code\n";
    exit( 1 );
}

if ( $model_count <= 0 ) {
    echo "No models returned\n";
    exit( 1 );
}

if ( ! is_array( $rate_limits ) || ! array_key_exists( 'requests_remaining', $rate_limits ) ) {
    echo "Rate limit info missing\n";
    exit( 1 );
}

echo "api-tester-gpt5-mini.test.php passed\n";
