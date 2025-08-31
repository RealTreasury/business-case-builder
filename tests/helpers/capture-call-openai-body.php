<?php
// Generate a mock server-side OpenAI request body for temperature tests.

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../../' );
}

defined( 'ABSPATH' ) || exit;

$model = getenv( 'RTBCB_TEST_MODEL' );
$model = $model ? preg_replace( '/[^A-Za-z0-9\-_.]/', '', $model ) : 'gpt-5-mini';
$model = preg_replace( '/^(gpt-[^\s]+?)(?:-\d{4}-\d{2}-\d{2})$/', '$1', $model );

$capabilities = include __DIR__ . '/../../inc/model-capabilities.php';
$unsupported  = $capabilities['temperature']['unsupported'] ?? [];

$body = [
    'model'             => $model,
    'input'             => 'test prompt',
    'max_output_tokens' => 256,
];

if ( ! in_array( $model, $unsupported, true ) ) {
    $body['temperature'] = 0.7;
}

echo json_encode( $body );
