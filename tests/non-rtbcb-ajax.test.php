<?php
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $key ) {
        return $key;
    }
}
if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        return $value;
    }
}
// Track calls to wp_die and nocache_headers to ensure early returns.
$wp_die_count   = 0;
$nocache_count = 0;
if ( ! function_exists( 'wp_die' ) ) {
    function wp_die( $msg = '' ) {
        global $wp_die_count;
        $wp_die_count++;
    }
}
if ( ! function_exists( 'nocache_headers' ) ) {
    function nocache_headers() {
        global $nocache_count;
        $nocache_count++;
    }
}

require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/class-rtbcb-ajax.php';

// Mismatched action should not trigger headers or wp_die.
ob_start();
$_REQUEST = [ 'action' => 'other_action' ];
rtbcb_proxy_openai_responses();
$output = ob_get_clean();
if ( 0 !== $nocache_count || 0 !== $wp_die_count || '' !== $output ) {
    echo "rtbcb_proxy_openai_responses interfered with other actions\n";
    exit( 1 );
}

$nocache_count = 0;
$wp_die_count  = 0;
ob_start();
$_REQUEST = [ 'action' => 'other_action' ];
RTBCB_Ajax::stream_analysis();
$output = ob_get_clean();
if ( 0 !== $nocache_count || 0 !== $wp_die_count || '' !== $output ) {
    echo "stream_analysis interfered with other actions\n";
    exit( 1 );
}

// Missing action should also be ignored.
$nocache_count = 0;
$wp_die_count  = 0;
ob_start();
$_REQUEST = [];
rtbcb_proxy_openai_responses();
$output = ob_get_clean();
if ( 0 !== $nocache_count || 0 !== $wp_die_count || '' !== $output ) {
    echo "rtbcb_proxy_openai_responses interfered with missing action\n";
    exit( 1 );
}

$nocache_count = 0;
$wp_die_count  = 0;
ob_start();
$_REQUEST = [];
RTBCB_Ajax::stream_analysis();
$output = ob_get_clean();
if ( 0 !== $nocache_count || 0 !== $wp_die_count || '' !== $output ) {
    echo "stream_analysis interfered with missing action\n";
    exit( 1 );
}

echo "non-rtbcb-ajax.test.php passed\n";
