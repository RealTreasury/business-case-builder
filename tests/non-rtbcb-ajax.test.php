<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/wp-stubs.php';
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_]/', '', strtolower( $key ) );
	}
}
ob_start();
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/class-rtbcb-ajax.php';
ob_end_clean();

// Ensure non-RTBCB actions produce no output or headers.
header_remove();
$_REQUEST['action'] = 'unrelated_action';
ob_start();
rtbcb_proxy_openai_responses();
$output = ob_get_clean();
$headers = headers_list();
if ( '' !== $output || ! empty( $headers ) ) {
	echo "rtbcb_proxy_openai_responses affected non-RTBCB action\n";
	exit( 1 );
}

header_remove();
$_REQUEST['action'] = 'unrelated_action';
ob_start();
RTBCB_Ajax::stream_analysis();
$output = ob_get_clean();
$headers = headers_list();
if ( '' !== $output || ! empty( $headers ) ) {
	echo "RTBCB_Ajax::stream_analysis affected non-RTBCB action\n";
	exit( 1 );
}

echo "non-rtbcb-ajax.test.php passed\n";
