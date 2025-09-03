<?php
if ( ! defined( 'ABSPATH' ) ) {
define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

define( 'DOING_AJAX', true );
define( 'IS_WPCOM', true );

if ( ! function_exists( 'check_ajax_referer' ) ) {
function check_ajax_referer( $action, $query_arg = false, $die = true ) {
return true;
}
}
if ( ! function_exists( 'sanitize_key' ) ) {
function sanitize_key( $key ) {
return $key;
}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
function sanitize_text_field( $text ) {
return $text;
}
}
if ( ! function_exists( 'wp_unslash' ) ) {
function wp_unslash( $value ) {
return $value;
}
}
if ( ! function_exists( '__' ) ) {
function __( $text, $domain = null ) {
return $text;
}
}
if ( ! function_exists( 'wp_die' ) ) {
function wp_die( $msg = '' ) {}
}
if ( ! function_exists( 'sanitize_email' ) ) {
function sanitize_email( $email ) {
return $email;
}
}
if ( ! function_exists( 'wp_json_encode' ) ) {
function wp_json_encode( $data ) {
return json_encode( $data );
}
}
if ( ! function_exists( 'nocache_headers' ) ) {
function nocache_headers() {}
}
if ( ! function_exists( 'header' ) ) {
function header( $str ) {}
}
if ( ! function_exists( 'get_option' ) ) {
function get_option( $name, $default = null ) {
return $default;
}
}

require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/class-rtbcb-ajax.php';

$sent_error = null;
if ( ! function_exists( 'wp_send_json_error' ) ) {
function wp_send_json_error( $data = null, $status = null ) {
global $sent_error;
$sent_error = [
'data'   => $data,
'status' => $status,
];
}
}
if ( ! function_exists( 'wp_send_json_success' ) ) {
function wp_send_json_success( $data = null ) {}
}

$_REQUEST = [ 'action' => 'rtbcb_stream_analysis', 'rtbcb_nonce' => 'nonce' ];
RTBCB_Ajax::stream_analysis();
if ( 'streaming_unsupported' !== ( $sent_error['data']['code'] ?? '' ) ) {
echo "stream_analysis fallback not triggered\n";
exit( 1 );
}

$sent_error = null;
$_POST    = [ 'body' => json_encode( [ 'foo' => 'bar' ] ), 'nonce' => 'nonce' ];
$_REQUEST = [ 'action' => 'rtbcb_openai_responses' ];
rtbcb_proxy_openai_responses();
if ( 'streaming_unsupported' !== ( $sent_error['data']['code'] ?? '' ) ) {
echo "proxy fallback not triggered\n";
exit( 1 );
}

echo "wpcom-sse-fallback.test.php passed\n";
