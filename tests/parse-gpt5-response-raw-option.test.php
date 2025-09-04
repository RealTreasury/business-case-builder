<?php
if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/../' );
}
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'get_option' ) ) {
        function get_option( $name, $default = '' ) {
                return $default;
        }
}

require_once __DIR__ . '/../inc/class-rtbcb-response-handler.php';

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
        function wp_remote_retrieve_body( $response ) {
                return $response['body'] ?? '';
        }
}

$mock_response = [
        'body' => json_encode( [ 'output_text' => 'hello world' ] ),
];

$parser = new RTBCB_Response_Handler();
$no_raw = $parser->parse( $mock_response );
if ( ! empty( $no_raw['raw'] ) ) {
        echo "Raw payload should be empty by default\n";
        exit( 1 );
}

$with_raw = $parser->parse( $mock_response, true );
if ( empty( $with_raw['raw'] ) ) {
        echo "Raw payload missing when requested\n";
        exit( 1 );
}

echo "parse-gpt5-response-raw-option.test.php passed\n";
