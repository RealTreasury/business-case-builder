<?php
require_once __DIR__ . '/wp-stubs.php';
require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		return $response['body'] ?? '';
	}
}

$mock_response = [
	'body' => json_encode( [ 'output_text' => 'hello world' ] ),
];

$no_raw = rtbcb_parse_gpt5_response( $mock_response );
if ( ! empty( $no_raw['raw'] ) ) {
	echo "Raw payload should be empty by default\n";
	exit( 1 );
}

$with_raw = rtbcb_parse_gpt5_response( $mock_response, true );
if ( empty( $with_raw['raw'] ) ) {
	echo "Raw payload missing when requested\n";
	exit( 1 );
}

echo "parse-gpt5-response-raw-option.test.php passed\n";
