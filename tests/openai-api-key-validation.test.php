<?php

define( 'ABSPATH', __DIR__ . '/../' );

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        // No-op for testing.
    }
}

require_once __DIR__ . '/../inc/helpers.php';

$valid_keys = [
    'sk-' . str_repeat( 'A', 20 ) . '_' . str_repeat( 'B', 27 ),
    'sk-' . str_repeat( 'A', 20 ) . ':' . str_repeat( 'B', 27 ),
];

foreach ( $valid_keys as $key ) {
    if ( ! rtbcb_is_valid_openai_api_key( $key ) ) {
        echo "Valid key failed: {$key}\n";
        exit( 1 );
    }
}

$invalid_keys = [
    'sk-' . str_repeat( 'A', 47 ),
    'sk-' . str_repeat( 'A', 20 ) . '+' . str_repeat( 'B', 27 ),
];

foreach ( $invalid_keys as $key ) {
    if ( rtbcb_is_valid_openai_api_key( $key ) ) {
        echo "Invalid key passed: {$key}\n";
        exit( 1 );
    }
}

echo "openai-api-key-validation.test.php passed\n";
