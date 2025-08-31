<?php
require_once __DIR__ . '/../inc/class-rtbcb-llm.php';

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		return $response['body'] ?? '';
	}
}

$log_file	= __DIR__ . '/reasoning-first-output.log';
$prev_error = ini_get( 'error_log' );
ini_set( 'error_log', $log_file );

$mock_response = [
	'body' => json_encode(
		[
			'output' => [
				[
					'id'	  => 'reasoning',
					'type'	  => 'reasoning',
					'content' => [
						[
							'type' => 'reasoning',
							'text' => 'thinking',
						],
					],
				],
				[
					'id'	  => 'message',
					'type'	  => 'message',
					'content' => [
						[
							'type' => 'output_text',
							'text' => 'This is a meaningful response message.',
						],
					],
				],
			],
		]
	),
];

$result = rtbcb_parse_gpt5_response( $mock_response );
$log	= file_exists( $log_file ) ? trim( file_get_contents( $log_file ) ) : '';
ini_set( 'error_log', $prev_error );
@unlink( $log_file );

if ( empty( $result['output_text'] ) ) {
	echo "Empty response detected\n";
	exit( 1 );
}

if ( 'This is a meaningful response message.' !== $result['output_text'] ) {
	echo "Unexpected output_text\n";
	exit( 1 );
}

if ( false === strpos( $log, 'Parsed response' ) ) {
	echo "Missing parsed response log\n";
	exit( 1 );
}

if ( false !== strpos( $log, 'Detected trivial response' ) ) {
	echo "Unexpected trivial response log\n";
	exit( 1 );
}

echo "reasoning-first-output.test.php passed\n";
