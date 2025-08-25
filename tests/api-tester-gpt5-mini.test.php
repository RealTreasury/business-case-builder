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

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $text ) {
        return $text;
    }
}

if ( ! function_exists( 'wp_remote_get' ) ) {
    function wp_remote_get( $url, $args ) {
        return [
            'body'    => json_encode( [ 'data' => [] ] ),
            'headers' => [],
        ];
    }
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
    function wp_remote_retrieve_response_code( $response ) {
        return 200;
    }
}

if ( ! function_exists( 'wp_remote_retrieve_headers' ) ) {
    function wp_remote_retrieve_headers( $response ) {
        return [];
    }
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
    function wp_remote_retrieve_body( $response ) {
        return $response['body'] ?? '';
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) {
        return false;
    }
}

require_once __DIR__ . '/../inc/enhanced-ajax-handlers.php';
$api_key = getenv( 'OPENAI_API_KEY' );
$api_key = sanitize_text_field( $api_key );

if ( empty( $api_key ) ) {
    echo __( 'OPENAI_API_KEY not set; skipping API connection test', 'rtbcb' ) . "\n";
    exit( 0 );
}

$result = RTBCB_API_Tester::test_connection( $api_key );
if ( empty( $result['success'] ) ) {
    echo __( 'API connection test failed', 'rtbcb' ) . "\n";
    exit( 1 );
}

echo __( 'api-tester-gpt5-mini.test.php passed', 'rtbcb' ) . "\n";
