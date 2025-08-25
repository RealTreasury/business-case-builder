<?php
// Mock environment for rtbcb_save_dashboard_settings test.

// Storage for options.
$GLOBALS['rtbcb_test_options'] = [];
$response_json = null;

function add_action( $hook, $callback ) {}
function check_ajax_referer( $action, $query_arg = false, $die = true ) {
    return true;
}
function current_user_can( $cap ) {
    return true;
}
function sanitize_text_field( $str ) {
    $str = is_scalar( $str ) ? (string) $str : '';
    $str = preg_replace( '/[\r\n\t\0\x0B]/', '', $str );
    return trim( $str );
}
function wp_unslash( $value ) {
    return $value;
}
function wp_json_encode( $data ) {
    return json_encode( $data );
}
function __( $text, $domain = 'rtbcb' ) {
    return $text;
}
function rtbcb_is_valid_openai_api_key( $api_key ) {
    $api_key = sanitize_text_field( $api_key );
    if ( 0 !== strncmp( $api_key, 'sk-', 3 ) ) {
        return false;
    }
    return strlen( $api_key ) >= 20;
}
function update_option( $name, $value ) {
    $GLOBALS['rtbcb_test_options'][ $name ] = $value;
}
function get_option( $name, $default = '' ) {
    return $GLOBALS['rtbcb_test_options'][ $name ] ?? $default;
}
function wp_send_json_success( $data = null ) {
    global $response_json;
    $data['api_valid'] = rtbcb_is_valid_openai_api_key( get_option( 'rtbcb_openai_api_key' ) );
    $response_json = [
        'success' => true,
        'data'    => $data,
    ];
}
function wp_send_json_error( $data = null ) {
    global $response_json;
    $response_json = [
        'success' => false,
        'data'    => $data,
    ];
}

define( 'ABSPATH', true );
require_once __DIR__ . '/../inc/enhanced-ajax-handlers.php';

$_POST = [
    'nonce'                => 'testnonce',
    'rtbcb_openai_api_key' => 'sk-valid-openai-key-1234567890',
];

rtbcb_save_dashboard_settings();

$stored_key = get_option( 'rtbcb_openai_api_key' );
if ( 'sk-valid-openai-key-1234567890' !== $stored_key ) {
    echo "API key was not saved correctly\n";
    exit( 1 );
}
if ( empty( $response_json ) || ! $response_json['success'] || empty( $response_json['data']['api_valid'] ) ) {
    echo "Response did not indicate API key validity\n";
    exit( 1 );
}

echo "dashboard-api-key.test.php passed\n";
