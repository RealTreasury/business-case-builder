<?php
if ( ! defined( 'ABSPATH' ) ) {
define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/wp-stubs.php';

if ( ! function_exists( 'sanitize_key' ) ) {
function sanitize_key( $key ) {
return $key;
}
}
if ( ! function_exists( 'wp_send_json_error' ) ) {
function wp_send_json_error( $data = null, $status = null ) {}
}
if ( ! function_exists( 'wp_send_json_success' ) ) {
function wp_send_json_success( $data = null ) {}
}

$headers = [];
if ( ! function_exists( 'nocache_headers' ) ) {
function nocache_headers() {
global $headers;
$headers[] = 'nocache';
}
}
if ( ! function_exists( 'header' ) ) {
function header( $str ) {
global $headers;
$headers[] = $str;
}
}

require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/class-rtbcb-ajax.php';

ob_start();
$_REQUEST = [ 'action' => 'other_action' ];
rtbcb_proxy_openai_responses();
$output = ob_get_clean();
if ( ! empty( $headers ) || '' !== $output ) {
echo "proxy emitted headers for other action\n";
exit( 1 );
}

$headers = [];
ob_start();
$_REQUEST = [ 'action' => 'another_action' ];
RTBCB_Ajax::stream_analysis();
$output = ob_get_clean();
if ( ! empty( $headers ) || '' !== $output ) {
echo "stream_analysis emitted headers for other action\n";
exit( 1 );
}

echo "non-rtbcb-ajax.test.php passed\n";
